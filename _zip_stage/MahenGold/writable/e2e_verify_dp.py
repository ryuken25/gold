#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""End-to-end verifikasi alur DP (uang muka) MahenGold terhadap app yang berjalan.

Alur: register pelanggan -> buat pesanan KREDIT (upload KTP) -> admin setujui
(-> kredit auto) -> pelanggan upload bukti DP -> admin verifikasi DP.
Cek di tiap langkah: tampilan (teks di halaman) + state DB (pengajuan/bukti).
"""
import struct
import subprocess
import sys
import time
import zlib
import pathlib

from playwright.sync_api import sync_playwright

BASE = "http://localhost:8080"  # samakan dgn app.baseURL (action form absolut ke host ini)
MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
DB = "mahengold_demo"
SHOTS = pathlib.Path(__file__).resolve().parent / "e2e_dp"
SHOTS.mkdir(exist_ok=True)

ADMIN = {"email": "admin@mahengold.test", "password": "admin123"}
TS = str(int(time.time()))
PEL = {
    "nama": "E2E Pelanggan",
    "email": f"e2e.dp.{TS}@mahengold.test",
    "no_telepon": "081299990000",
    "password": "rahasia123",
}

results = []  # (ok, label, detail)


def check(ok, label, detail=""):
    results.append((ok, label, detail))
    print(("PASS " if ok else "FAIL ") + label + ((" :: " + detail) if detail else ""))


def q(sql):
    out = subprocess.run([MYSQL, "-uroot", "-N", "-e", sql, DB],
                         capture_output=True, text=True)
    if out.returncode != 0:
        return "ERR:" + out.stderr.strip()
    return out.stdout.strip()


def png_bytes():
    def chunk(typ, data):
        body = typ + data
        return struct.pack(">I", len(data)) + body + struct.pack(">I", zlib.crc32(body) & 0xffffffff)
    sig = b"\x89PNG\r\n\x1a\n"
    ihdr = struct.pack(">IIBBBBB", 1, 1, 8, 2, 0, 0, 0)
    idat = zlib.compress(b"\x00\xff\x00\x00")
    return sig + chunk(b"IHDR", ihdr) + chunk(b"IDAT", idat) + chunk(b"IEND", b"")


KTP = {"name": "ktp.png", "mimeType": "image/png", "buffer": png_bytes()}
BUKTI = {"name": "dp.png", "mimeType": "image/png", "buffer": png_bytes()}

CSRF_NAME = None  # field name, konstan se-app (dari data-csrf-name form pesanan)


def main():
    global CSRF_NAME
    with sync_playwright() as p:
        browser = p.chromium.launch()
        ctx = browser.new_context()
        page = ctx.new_page()

        def login(email, password):
            page.goto(f"{BASE}/login")
            page.fill('input[name="email"]', email)
            page.fill('input[name="password"]', password)
            page.click('button[type="submit"]')
            page.wait_for_load_state("networkidle")

        def csrf_in_form(action_substr):
            return page.evaluate(
                """([sub, name]) => {
                    const fs=[...document.querySelectorAll('form')];
                    const f=fs.find(x=>(x.getAttribute('action')||'').includes(sub));
                    if(!f) return null;
                    const i=f.querySelector(`input[name="${name}"]`);
                    return i ? i.value : null;
                }""",
                [action_substr, CSRF_NAME],
            )

        # 1) Register pelanggan baru -------------------------------------
        page.goto(f"{BASE}/register")
        page.fill('input[name="nama"]', PEL["nama"])
        page.fill('input[name="email"]', PEL["email"])
        page.fill('input[name="no_telepon"]', PEL["no_telepon"])
        page.fill('input[name="password"]', PEL["password"])
        page.fill('input[name="password_confirm"]', PEL["password"])
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        uid = q(f"SELECT id FROM users WHERE email='{PEL['email']}' LIMIT 1")
        check(uid.isdigit(), "registrasi pelanggan membuat user", f"user_id={uid}")

        # login bersih sebagai pelanggan
        ctx.clear_cookies()
        login(PEL["email"], PEL["password"])

        # 2) Buat pesanan KREDIT (upload KTP) ke endpoint asli -----------
        prod_id = q("SELECT id FROM produk_emas WHERE kode_produk='MGD-002' LIMIT 1")
        page.goto(f"{BASE}/produk/MGD-002")
        meta = page.evaluate(
            """() => {
                const f=document.querySelector('#waPengajuanForm');
                const name=f.getAttribute('data-csrf-name');
                return {name, val: f.querySelector(`input[name="${name}"]`).value};
            }"""
        )
        CSRF_NAME = meta["name"]
        resp = ctx.request.post(
            f"{BASE}/pesanan",
            headers={"X-Requested-With": "XMLHttpRequest"},
            multipart={
                CSRF_NAME: meta["val"],
                "produk_id": prod_id,
                "metode_pembayaran": "kredit",
                "nama": PEL["nama"],
                "no_telepon": PEL["no_telepon"],
                "alamat": "Jl. Uji Coba No. 1 Denpasar",
                "tenor_bulan": "12",
                "periode_angsuran": "bulanan",
                "uang_muka": "999",  # sengaja salah -> server harus paksa 200000
                "foto_ktp": KTP,
            },
        )
        body = resp.json()
        kode = body.get("kode_pesanan", "")
        check(resp.ok and body.get("success") is True,
              "POST /pesanan kredit berhasil (AJAX)", f"http={resp.status} kode={kode}")

        peng_id = q(f"SELECT id FROM pengajuan WHERE kode_pesanan='{kode}' LIMIT 1")
        check(peng_id.isdigit(), "pesanan tersimpan di DB", f"pengajuan_id={peng_id}")
        dp_db = q(f"SELECT uang_muka FROM pengajuan WHERE id={peng_id}")
        check(dp_db.startswith("200000"),
              "DP dipaksa server ke Rp 200.000 (abaikan input user)", f"uang_muka={dp_db}")
        st = q(f"SELECT status FROM pengajuan WHERE id={peng_id}")
        check(st == "baru", "status awal pesanan = baru", st)

        # 3) Admin setujui -> kredit auto ------------------------------
        ctx.clear_cookies()
        login(ADMIN["email"], ADMIN["password"])
        page.goto(f"{BASE}/admin/pengajuan/{peng_id}")
        val = csrf_in_form("/verifikasi")
        rv = ctx.request.post(f"{BASE}/admin/pengajuan/{peng_id}/verifikasi",
                              form={CSRF_NAME: val})
        check(rv.ok, "admin verifikasi pesanan (POST)", f"http={rv.status}")
        st = q(f"SELECT status FROM pengajuan WHERE id={peng_id}")
        check(st == "disetujui", "status pesanan -> disetujui", st)
        krd = q(f"SELECT id,uang_muka FROM kredit WHERE pengajuan_id={peng_id} LIMIT 1")
        check(krd and not krd.startswith("ERR") and krd != "",
              "kredit + jadwal angsuran otomatis dibuat", f"kredit={krd}")

        # 4) Pelanggan: form DP tampil, lalu upload bukti --------------
        ctx.clear_cookies()
        login(PEL["email"], PEL["password"])
        page.goto(f"{BASE}/akun/pesanan/{peng_id}")
        page.wait_for_load_state("networkidle")
        page.screenshot(path=str(SHOTS / "1_dp_form.png"), full_page=True)
        txt = page.inner_text("body")
        check("Uang Muka (DP)" in txt and "Upload Bukti DP" in txt,
              "form upload bukti DP tampil di halaman pesanan (status disetujui)")
        check("Rp 200.000" in txt, "nominal DP Rp 200.000 tampil ke pelanggan")

        val = csrf_in_form("/bukti-dp")
        ru = ctx.request.post(
            f"{BASE}/akun/pesanan/{peng_id}/bukti-dp",
            multipart={
                CSRF_NAME: val,
                "nama_pengirim": "E2E Pelanggan",
                "no_rekening": "1234567890",
                "bank_pengirim": "BCA",
                "bukti": BUKTI,
            },
        )
        check(ru.ok, "POST upload bukti DP", f"http={ru.status}")

        b = q(f"SELECT tipe,status,nominal FROM bukti_pembayaran WHERE pengajuan_id={peng_id} AND tipe='dp' ORDER BY id DESC LIMIT 1")
        check(b.startswith("dp\tmenunggu"), "bukti DP tersimpan status=menunggu (PENDING)", b)
        ps = q(f"SELECT pembayaran_status FROM pengajuan WHERE id={peng_id}")
        check(ps == "menunggu", "pengajuan.pembayaran_status -> menunggu", ps)

        page.goto(f"{BASE}/akun/pesanan/{peng_id}")
        page.wait_for_load_state("networkidle")
        page.screenshot(path=str(SHOTS / "2_dp_pending.png"), full_page=True)
        txt = page.inner_text("body")
        check("menunggu verifikasi admin" in txt.lower(),
              "pelanggan melihat status 'menunggu verifikasi admin'")

        # 5) Admin verifikasi DP ---------------------------------------
        bukti_id = q(f"SELECT id FROM bukti_pembayaran WHERE pengajuan_id={peng_id} AND tipe='dp' ORDER BY id DESC LIMIT 1")
        ctx.clear_cookies()
        login(ADMIN["email"], ADMIN["password"])
        page.goto(f"{BASE}/admin/pembayaran?tipe=dp")
        page.wait_for_load_state("networkidle")
        page.screenshot(path=str(SHOTS / "3_admin_dp_queue.png"), full_page=True)
        atxt = page.inner_text("body")
        check(bool(kode) and kode in atxt,
              "bukti DP muncul di antrean verifikasi admin", f"kode={kode}")
        val = csrf_in_form(f"/pembayaran/{bukti_id}/verifikasi")
        rvd = ctx.request.post(f"{BASE}/admin/pembayaran/{bukti_id}/verifikasi",
                               form={CSRF_NAME: val})
        check(rvd.ok, "admin verifikasi bukti DP (POST)", f"http={rvd.status}")
        b = q(f"SELECT status,diverifikasi_oleh FROM bukti_pembayaran WHERE id={bukti_id}")
        check(b.startswith("terverifikasi"), "bukti DP -> terverifikasi", b)
        ps = q(f"SELECT pembayaran_status FROM pengajuan WHERE id={peng_id}")
        check(ps == "terverifikasi", "pengajuan.pembayaran_status -> terverifikasi", ps)

        # 6) Pelanggan lihat status terverifikasi ----------------------
        ctx.clear_cookies()
        login(PEL["email"], PEL["password"])
        page.goto(f"{BASE}/akun/pesanan/{peng_id}")
        page.wait_for_load_state("networkidle")
        page.screenshot(path=str(SHOTS / "4_dp_verified.png"), full_page=True)
        txt = page.inner_text("body")
        check("Bukti DP terverifikasi" in txt,
              "pelanggan melihat 'Bukti DP terverifikasi'")

        browser.close()

    print("\n==== RINGKASAN ====")
    passed = sum(1 for ok, _, _ in results if ok)
    print(f"{passed}/{len(results)} langkah PASS")
    print(f"pengajuan_id={peng_id}  kode={kode}")
    print(f"screenshot: {SHOTS}")
    sys.exit(0 if passed == len(results) else 1)


if __name__ == "__main__":
    main()
