#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""End-to-end verifikasi alur CASH MahenGold terhadap app yang berjalan.

Fokus dua perbaikan:
  BUG-1 — order CASH wajib menyimpan foto KTP (dulu hanya kredit).
  BUG-2 — admin punya UI verifikasi bukti pembayaran cash di detail pesanan,
          sehingga pembayaran_status bisa lepas dari 'menunggu' dan tombol
          "Kirim Pesanan" tidak terkunci selamanya.

Alur: register pelanggan -> pesan CASH (KTP + bukti) -> admin verifikasi
pesanan -> admin verifikasi bukti cash -> kirim -> terima (auto selesai)
-> cek sisi pelanggan.

Semua aksi admin dilakukan lewat klik elemen asli (MahenDialog), bukan
request manual, supaya CSRF & session ikut apa adanya.
"""
import re
import struct
import subprocess
import sys
import time
import zlib
import pathlib

from playwright.sync_api import sync_playwright

BASE = "http://localhost:8080"  # samakan dgn app.baseURL (host beda -> cookie/CSRF putus)
MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
DB = "mahengold_demo"
ROOT = pathlib.Path(__file__).resolve().parent          # writable/
SHOTS = ROOT / "e2e_cash"
SHOTS.mkdir(exist_ok=True)

ADMIN = {"email": "admin@mahengold.test", "password": "admin123"}
TS = str(int(time.time()))
PEL = {
    "nama": "E2E Cash Pelanggan",
    "email": f"e2e.cash.{TS}@mahengold.test",
    "no_telepon": "081277770000",
    "password": "rahasia123",
}

results = []  # (ok, label, detail)


def check(ok, label, detail=""):
    results.append((bool(ok), label, detail))
    print(("PASS " if ok else "FAIL ") + label + ((" :: " + detail) if detail else ""))
    return bool(ok)


def q(sql):
    out = subprocess.run([MYSQL, "-uroot", "-N", "-e", sql, DB],
                         capture_output=True, text=True)
    if out.returncode != 0:
        return "ERR:" + out.stderr.strip()
    return out.stdout.strip()


def wait_db(sql, expected, timeout=45.0, interval=0.5):
    """Poll query sampai hasilnya == expected (aksi admin async via fetch)."""
    deadline = time.time() + timeout
    val = q(sql)
    while val != expected and time.time() < deadline:
        time.sleep(interval)
        val = q(sql)
    return val


def png_bytes():
    def chunk(typ, data):
        body = typ + data
        return struct.pack(">I", len(data)) + body + struct.pack(">I", zlib.crc32(body) & 0xffffffff)
    sig = b"\x89PNG\r\n\x1a\n"
    ihdr = struct.pack(">IIBBBBB", 1, 1, 8, 2, 0, 0, 0)
    idat = zlib.compress(b"\x00\xff\x00\x00")
    return sig + chunk(b"IHDR", ihdr) + chunk(b"IDAT", idat) + chunk(b"IEND", b"")


KTP = {"name": "ktp-cash.png", "mimeType": "image/png", "buffer": png_bytes()}
BUKTI = {"name": "bukti-cash.png", "mimeType": "image/png", "buffer": png_bytes()}


# ---------------------------------------------------------------- MahenDialog
# Shell modal dibangun di public/assets/js/mahengold-dialog.js:
#   #mgDialogShell > .modal ... #mgDialogFooter (tombol / form dialog)
DIALOG = "#mgDialogShell .modal"
FOOTER = "#mgDialogFooter"


def _post_pred(url_part):
    return lambda r: url_part in r.url and r.request.method == "POST"


def dialog_confirm(page, url_part, timeout=120000):
    """Klik tombol konfirmasi MahenDialog dan TUNGGU response POST-nya sampai
    diterima browser.

    Penting: jangan cuma poll DB lalu navigasi. CSRF-nya cookie-based dengan
    regenerate=true, jadi kalau kita pindah halaman sebelum browser sempat
    memproses Set-Cookie dari response AJAX, token di meta halaman berikutnya
    jadi basi dan POST berikutnya ditolak SecurityException.
    """
    page.wait_for_selector(f"{DIALOG}.show", state="visible", timeout=30000)
    btn = page.locator(f"{FOOTER} button").first
    btn.wait_for(state="visible", timeout=30000)
    label = btn.inner_text().strip()
    with page.expect_response(_post_pred(url_part), timeout=timeout) as info:
        btn.click()
    return label, info.value


def dialog_form_submit(page, values, url_part, timeout=120000):
    """Isi MahenDialog.form lalu submit, tunggu response POST-nya."""
    page.wait_for_selector(f"{FOOTER} form.mg-dialog-form", state="visible", timeout=30000)
    for name, val in values.items():
        sel = f'{FOOTER} form.mg-dialog-form [name="{name}"]'
        page.wait_for_selector(sel, state="visible", timeout=30000)
        tag = page.eval_on_selector(sel, "el => el.tagName.toLowerCase()")
        if tag == "select":
            page.select_option(sel, val)
        else:
            page.fill(sel, val)
    with page.expect_response(_post_pred(url_part), timeout=timeout) as info:
        page.click(f'{FOOTER} form.mg-dialog-form button[type="submit"]')
    return info.value


def dialog_ok(page, timeout=30000):
    """Tutup dialog hasil (Berhasil/Gagal) dgn klik OK — persis kelakuan admin."""
    try:
        ok = page.locator(f'{FOOTER} button:text-is("OK")')
        ok.wait_for(state="visible", timeout=timeout)
        title = page.inner_text("#mgDialogTitle")
        msg = page.inner_text("#mgDialogMessage")
        ok.click()
        page.wait_for_load_state("load", timeout=timeout)
        return f"{title}: {msg}"
    except Exception as e:
        return "dialog hasil tidak muncul: " + str(e)[:120]


def resp_brief(resp):
    try:
        return f"http={resp.status} body={resp.text()[:200]}"
    except Exception:
        return f"http={resp.status}"


def dialog_text(page):
    try:
        return page.inner_text(FOOTER, timeout=3000) + " | " + page.inner_text("#mgDialogMessage", timeout=3000)
    except Exception:
        return ""


def main():
    peng_id = kode = ""
    with sync_playwright() as p:
        browser = p.chromium.launch()
        cctx = browser.new_context()   # pelanggan
        actx = browser.new_context()   # admin
        cpage = cctx.new_page()
        apage = actx.new_page()
        for pg in (cpage, apage):
            pg.set_default_timeout(90000)

        def login(page, email, password):
            page.goto(f"{BASE}/login")
            page.fill('input[name="email"]', email)
            page.fill('input[name="password"]', password)
            page.click('button[type="submit"]')
            page.wait_for_load_state("networkidle")

        # ============================================================ 1. Register
        cpage.goto(f"{BASE}/register")
        cpage.fill('input[name="nama"]', PEL["nama"])
        cpage.fill('input[name="email"]', PEL["email"])
        cpage.fill('input[name="no_telepon"]', PEL["no_telepon"])
        cpage.fill('input[name="password"]', PEL["password"])
        cpage.fill('input[name="password_confirm"]', PEL["password"])
        cpage.click('button[type="submit"]')
        cpage.wait_for_load_state("networkidle")
        uid = q(f"SELECT id FROM users WHERE email='{PEL['email']}' LIMIT 1")
        check(uid.isdigit(), "[1] registrasi pelanggan membuat user", f"user_id={uid}")
        if not uid.isdigit():
            raise SystemExit("registrasi gagal, stop.")

        cctx.clear_cookies()
        login(cpage, PEL["email"], PEL["password"])

        # ======================================================== 2. Pesanan CASH
        cpage.goto(f"{BASE}/katalog")
        cpage.wait_for_load_state("networkidle")
        cpage.wait_for_selector(".js-open-wa-modal")
        btn = cpage.locator(".js-open-wa-modal").first
        prod_kode = btn.get_attribute("data-kode")
        prod_id = btn.get_attribute("data-produk-id")
        btn.click()
        cpage.wait_for_selector("#waPengajuanModal.show", state="visible")

        # Radio-nya ketutup span ikon di dalam <label>, jadi klik label-nya
        # (persis seperti user beneran) supaya event change ikut jalan.
        cpage.click('label.metode-option[for="metode_cash"]')   # <- metode CASH
        cpage.wait_for_function("() => document.getElementById('metode_cash').checked")
        cpage.fill("#wa_nama", PEL["nama"])
        cpage.fill("#wa_no_telepon", PEL["no_telepon"])
        cpage.fill("#wa_alamat", "Jl. Uji Coba Cash No. 7, Denpasar")
        cpage.set_input_files("#wa_foto_ktp", KTP)
        cpage.set_input_files("#wa_bukti", BUKTI)
        cpage.fill('input[name="nama_pengirim"]', PEL["nama"])
        cpage.fill('input[name="no_rekening"]', "1234567890")
        cpage.fill('input[name="bank_pengirim"]', "BRI")
        metode_terpilih = cpage.eval_on_selector(
            '#waPengajuanModal input[name="metode_pembayaran"]:checked', "el => el.value")
        check(metode_terpilih == "cash", "[2a] metode pembayaran terpilih = cash", metode_terpilih)
        cpage.screenshot(path=str(SHOTS / "1_form_cash_terisi.png"), full_page=True)

        # Submit bisa lambat (~30s) karena EmailNotificationService konek SMTP sinkron.
        cpage.click('[form="waPengajuanForm"][type="submit"]')
        try:
            cpage.wait_for_selector("#orderSuccessModal.show", state="visible", timeout=120000)
            sukses_modal = True
        except Exception:
            sukses_modal = False
        cpage.screenshot(path=str(SHOTS / "2_order_sukses.png"), full_page=True)
        if not sukses_modal:
            check(False, "[2b] popup sukses order muncul", dialog_text(cpage) or cpage.inner_text("body")[:300])
        else:
            check(True, "[2b] popup sukses order muncul")

        row = q(f"SELECT id,kode_pesanan,metode_pembayaran,status,pembayaran_status "
                f"FROM pengajuan WHERE user_id={uid} ORDER BY id DESC LIMIT 1")
        cols = row.split("\t") if row and not row.startswith("ERR") else []
        check(len(cols) == 5, "[2c] pesanan tersimpan di DB", row)
        if len(cols) != 5:
            raise SystemExit("pesanan tidak tersimpan, stop.")
        peng_id, kode, metode_db, status_db, pay_db = cols
        check(metode_db == "cash", "[2d] metode_pembayaran tersimpan = cash", metode_db)
        check(status_db == "baru", "[2e] status awal pesanan = baru", status_db)
        check(pay_db == "menunggu", "[2f] pembayaran_status awal = menunggu (bukti cash pending)", pay_db)

        # ============================== 3. BUG-1: foto KTP order CASH tersimpan
        ktp_db = q(f"SELECT COALESCE(foto_ktp,'') FROM pengajuan WHERE id={peng_id}")
        check(ktp_db != "" and not ktp_db.startswith("ERR"),
              "[3a] BUG-1 pengajuan.foto_ktp TIDAK kosong untuk order CASH", f"foto_ktp={ktp_db!r}")
        ktp_path = ROOT / "uploads" / "ktp" / ktp_db if ktp_db else None
        check(bool(ktp_db) and ktp_path.is_file(),
              "[3b] BUG-1 file KTP benar-benar ada di writable/uploads/ktp/",
              str(ktp_path) if ktp_path else "-")

        bukti_row = q(f"SELECT id,tipe,status FROM bukti_pembayaran WHERE pengajuan_id={peng_id} "
                      f"ORDER BY id DESC LIMIT 1")
        bcols = bukti_row.split("\t") if bukti_row and not bukti_row.startswith("ERR") else []
        check(len(bcols) == 3 and bcols[1] == "cash" and bcols[2] == "menunggu",
              "[3c] bukti pembayaran cash tersimpan status=menunggu", bukti_row)
        bukti_id = bcols[0] if bcols else ""

        # ======================================================= 4. Login admin
        login(apage, ADMIN["email"], ADMIN["password"])
        apage.goto(f"{BASE}/admin/pengajuan/{peng_id}")
        apage.wait_for_load_state("networkidle")
        apage.screenshot(path=str(SHOTS / "3_admin_detail_ktp.png"), full_page=True)
        atxt = apage.inner_text("body")
        check("Foto KTP" in atxt, "[4] halaman detail pengajuan admin terbuka", f"url={apage.url}")

        # ============================ 5. Tampilan KTP di detail admin (BUG-1 UI)
        check("KTP belum diunggah" not in atxt,
              "[5a] BUG-1 halaman admin TIDAK menampilkan 'KTP belum diunggah'")
        ktp_url_path = f"/admin/pengajuan/{peng_id}/ktp"
        has_img = apage.evaluate(
            """(sub) => [...document.querySelectorAll('img')].some(i => (i.getAttribute('src')||'').includes(sub))""",
            ktp_url_path)
        check(has_img, "[5b] BUG-1 ada <img> dengan src /admin/pengajuan/{id}/ktp", ktp_url_path)
        rk = actx.request.get(f"{BASE}{ktp_url_path}")
        ctype = rk.headers.get("content-type", "")
        check(rk.ok and ctype.startswith("image/"),
              "[5c] BONUS GET url KTP balik 200 + content-type image",
              f"http={rk.status} content-type={ctype}")

        # ==================================================== 6. Verifikasi pesanan
        apage.click("#btnVerifikasi")
        lbl, rv = dialog_confirm(apage, f"/admin/pengajuan/{peng_id}/verifikasi")
        check(rv.ok, "[6a] POST verifikasi pesanan diterima server",
              f"tombol='{lbl}' " + resp_brief(rv))
        hasil = dialog_ok(apage)
        st = wait_db(f"SELECT status FROM pengajuan WHERE id={peng_id}", "disetujui", timeout=90)
        check(st == "disetujui", "[6b] status pesanan -> disetujui setelah Verifikasi Pesanan",
              f"status={st} | {hasil}")
        ps = q(f"SELECT pembayaran_status FROM pengajuan WHERE id={peng_id}")
        check(ps == "menunggu",
              "[6c] pembayaran_status TETAP menunggu (cash tidak auto-verified)", ps)

        # ========== 7. BUG-2: Kirim disabled + tombol verifikasi bukti tersedia
        apage.goto(f"{BASE}/admin/pengajuan/{peng_id}")
        apage.wait_for_load_state("networkidle")
        apage.screenshot(path=str(SHOTS / "4_disetujui_kirim_disabled.png"), full_page=True)
        kirim_dis = apage.locator('button:has-text("Kirim Pesanan")[disabled]')
        check(kirim_dis.count() == 1,
              "[7a] tombol 'Kirim Pesanan' ADA tapi DISABLED (pembayaran belum terverifikasi)",
              f"count_disabled={kirim_dis.count()} btnKirim_aktif={apage.locator('#btnKirim').count()}")
        verif_btn = apage.locator(".js-verif-bukti")
        check(verif_btn.count() >= 1,
              "[7b] BUG-2 tombol Verifikasi bukti (.js-verif-bukti) muncul di section Bukti Pembayaran",
              f"count={verif_btn.count()} tolak={apage.locator('.js-tolak-bukti').count()}")
        check("Menunggu verifikasi" in apage.inner_text("body"),
              "[7c] BUG-2 badge status bukti 'Menunggu verifikasi' tampil")

        # =========================================== 8. Verifikasi bukti cash
        if verif_btn.count() == 0:
            check(False, "[8] verifikasi bukti cash", "tombol .js-verif-bukti tidak ada — stop di sini")
        else:
            verif_btn.first.click()
            lbl, rv = dialog_confirm(apage, f"/admin/pembayaran/{bukti_id}/verifikasi")
            check(rv.ok, "[8a] BUG-2 POST verifikasi bukti cash diterima server",
                  f"tombol='{lbl}' " + resp_brief(rv))
            hasil = dialog_ok(apage)
            bst = wait_db(f"SELECT status FROM bukti_pembayaran WHERE id={bukti_id}",
                          "terverifikasi", timeout=90)
            check(bst == "terverifikasi", "[8b] bukti_pembayaran.status -> terverifikasi",
                  f"status={bst} | {hasil}")
            ps = wait_db(f"SELECT pembayaran_status FROM pengajuan WHERE id={peng_id}",
                         "terverifikasi", timeout=30)
            check(ps == "terverifikasi", "[8c] pengajuan.pembayaran_status -> terverifikasi", ps)
            apage.screenshot(path=str(SHOTS / "5_bukti_terverifikasi.png"), full_page=True)

        # ================================= 9. Kirim pesanan (tombol harus aktif)
        apage.goto(f"{BASE}/admin/pengajuan/{peng_id}")
        apage.wait_for_load_state("networkidle")
        apage.screenshot(path=str(SHOTS / "6_kirim_enabled.png"), full_page=True)
        kirim_aktif = apage.locator("#btnKirim:not([disabled])")
        n_dis = apage.locator('button:has-text("Kirim Pesanan")[disabled]').count()
        check(kirim_aktif.count() == 1,
              "[9a] BUG-2 tombol 'Kirim Pesanan' sekarang ENABLED setelah bukti terverifikasi",
              f"enabled={kirim_aktif.count()} disabled={n_dis}")
        if kirim_aktif.count() == 1:
            kirim_aktif.click()
            rk = dialog_form_submit(apage, {
                "metode_pengiriman": "resi",
                "referensi_pengiriman": "RESI-E2E-001",
            }, f"/admin/pengajuan/{peng_id}/kirim")
            check(rk.ok, "[9b] POST kirim pesanan diterima server", resp_brief(rk))
            hasil = dialog_ok(apage)
            st = wait_db(f"SELECT status FROM pengajuan WHERE id={peng_id}", "dikirim", timeout=90)
            check(st == "dikirim", "[9c] status pesanan -> dikirim", f"status={st} | {hasil}")
            ref = q(f"SELECT CONCAT(COALESCE(metode_pengiriman,''),'/',COALESCE(referensi_pengiriman,'')) "
                    f"FROM pengajuan WHERE id={peng_id}")
            check(ref == "resi/RESI-E2E-001", "[9d] metode & referensi pengiriman tersimpan", ref)
            apage.screenshot(path=str(SHOTS / "7_dikirim.png"), full_page=True)
        else:
            check(False, "[9c] status pesanan -> dikirim", "tombol Kirim masih terkunci")

        # ========================= 10. Konfirmasi diterima -> auto selesai (cash)
        apage.goto(f"{BASE}/admin/pengajuan/{peng_id}")
        apage.wait_for_load_state("networkidle")
        if apage.locator("#btnTerima").count() == 1:
            apage.click("#btnTerima")
            lbl, rt = dialog_confirm(apage, f"/admin/pengajuan/{peng_id}/terima")
            check(rt.ok, "[10a] POST konfirmasi diterima diterima server",
                  f"tombol='{lbl}' " + resp_brief(rt))
            hasil = dialog_ok(apage)
            st = wait_db(f"SELECT status FROM pengajuan WHERE id={peng_id}", "selesai", timeout=90)
            check(st == "selesai",
                  "[10b] status -> selesai (auto-complete cash + pembayaran terverifikasi)",
                  f"status={st} | {hasil}")
            akt = q(f"SELECT GROUP_CONCAT(aksi ORDER BY id) FROM pengajuan_aktivitas WHERE pengajuan_id={peng_id}")
            check("diterima" in akt and "selesai" in akt,
                  "[10c] aktivitas mencatat diterima + selesai", akt)
            apage.goto(f"{BASE}/admin/pengajuan/{peng_id}")
            apage.wait_for_load_state("networkidle")
            apage.screenshot(path=str(SHOTS / "8_selesai_admin.png"), full_page=True)
        else:
            check(False, "[10b] status -> selesai", "tombol #btnTerima tidak tersedia")

        # ============================================ 11. Sisi pelanggan
        cpage.goto(f"{BASE}/akun/pesanan/{peng_id}")
        cpage.wait_for_load_state("networkidle")
        cpage.screenshot(path=str(SHOTS / "9_pelanggan_selesai.png"), full_page=True)
        ctxt = cpage.inner_text("body")
        badge = cpage.locator(".feature-card .badge").first.inner_text().strip()
        check(badge == "Selesai", "[11a] badge status di halaman pelanggan = 'Selesai'", badge)
        aktif = cpage.locator(".order-stepper .stepper-item.active .stepper-label").inner_text().strip()
        n_done = cpage.locator(".order-stepper .stepper-item.completed").count()
        check(aktif == "Selesai" and n_done == 5,
              "[11b] timeline/stepper pelanggan berhenti di step 'Selesai' (5/5 completed)",
              f"active={aktif} completed={n_done}")
        check(kode in ctxt, "[11c] halaman pelanggan menampilkan kode pesanan", kode)

        browser.close()

    print("\n==== RINGKASAN ====")
    passed = sum(1 for ok, _, _ in results if ok)
    for ok, label, detail in results:
        if not ok:
            print("  FAIL -> " + label + ((" :: " + detail) if detail else ""))
    print(f"{passed}/{len(results)} assertion PASS")
    print(f"pengajuan_id={peng_id}  kode={kode}  produk_kode dipakai: lihat log di atas")
    print(f"screenshot: {SHOTS}")
    sys.exit(0 if passed == len(results) else 1)


if __name__ == "__main__":
    main()
