#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""E2E verifikasi: form Ajukan Pembelian punya field 'Bukti Pembayaran' untuk
kedua metode (cash & kredit), submit bikin pesanan + bukti tepat."""
import struct
import subprocess
import zlib
import pathlib
from playwright.sync_api import sync_playwright

BASE = "http://localhost:8080"
MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
DB = "mahengold_demo"
SHOTS = pathlib.Path(__file__).resolve().parent / "form_dp"
SHOTS.mkdir(exist_ok=True)
PEL = ("demo.pelanggan@mahengold.test", "demo1234")


def q(sql):
    o = subprocess.run([MYSQL, "-uroot", "-N", "-e", sql, DB], capture_output=True, text=True)
    return o.stdout.strip()


def png():
    def ch(t, d):
        b = t + d
        return struct.pack(">I", len(d)) + b + struct.pack(">I", zlib.crc32(b) & 0xffffffff)
    return (b"\x89PNG\r\n\x1a\n" + ch(b"IHDR", struct.pack(">IIBBBBB", 1, 1, 8, 2, 0, 0, 0))
            + ch(b"IDAT", zlib.compress(b"\x00\xff\x00\x00")) + ch(b"IEND", b""))


IMG = {"name": "x.png", "mimeType": "image/png", "buffer": png()}


def main():
    before_p = int(q("SELECT COUNT(*) FROM pengajuan") or 0)
    before_b = int(q("SELECT COUNT(*) FROM bukti_pembayaran") or 0)
    results = []

    with sync_playwright() as p:
        b = p.chromium.launch()
        ctx = b.new_context()
        pg = ctx.new_page()

        # Login
        pg.goto(f"{BASE}/login")
        pg.fill('input[name="email"]', PEL[0])
        pg.fill('input[name="password"]', PEL[1])
        pg.click('button[type="submit"]')
        pg.wait_for_load_state("networkidle")

        def open_modal(produk_kode):
            pg.goto(f"{BASE}/produk/{produk_kode}")
            pg.wait_for_load_state("networkidle")
            pg.click(".js-open-wa-modal")
            pg.wait_for_selector("#waPengajuanModal.show", timeout=5000)
            pg.wait_for_timeout(300)

        def scroll_to_bukti():
            pg.eval_on_selector("#wa_bukti", "el => el.scrollIntoView({block:'center'})")
            pg.wait_for_timeout(200)

        # === A. KREDIT mode =============================================
        open_modal("MGD-003")
        # Check bukti field VISIBLE in credit mode
        results.append(("KREDIT: field bukti tampil di form", pg.is_visible("#wa_bukti")))
        # Toggle to credit (default already), capture
        pg.click('label[for="metode_kredit"]')
        pg.wait_for_timeout(200)
        scroll_to_bukti()
        pg.screenshot(path=str(SHOTS / "form_kredit.png"))
        txt = pg.inner_text("body")
        results.append(("KREDIT: label 'Bukti Pembayaran Uang Muka (DP)'",
                        "Bukti Pembayaran Uang Muka (DP)" in txt))
        results.append(("KREDIT: bantuan menyebut 'Transfer DP'",
                        "Transfer DP" in txt))
        # Fill + submit
        pg.fill("#wa_nama", "Tester Kredit")
        pg.fill("#wa_no_telepon", "081298880001")
        pg.fill("#wa_alamat", "Jl. Kredit No. 1")
        pg.set_input_files("#wa_foto_ktp", IMG)
        pg.set_input_files("#wa_bukti", IMG)
        pg.fill('input[name="nama_pengirim"]', "Tester Kredit")
        pg.fill('input[name="bank_pengirim"]', "BCA")
        pg.click('[form="waPengajuanForm"][type="submit"]')
        pg.wait_for_url("**/akun/pesanan", timeout=45000)

        # === B. CASH mode ===============================================
        open_modal("MGD-006")
        # Toggle to cash
        pg.click('label[for="metode_cash"]')
        pg.wait_for_timeout(300)
        results.append(("CASH: field bukti tampil di form", pg.is_visible("#wa_bukti")))
        # Kredit-only field (KTP) should be hidden
        results.append(("CASH: field KTP tersembunyi",
                        not pg.is_visible("#wa_foto_ktp")))
        scroll_to_bukti()
        pg.screenshot(path=str(SHOTS / "form_cash.png"))
        txt = pg.inner_text("body")
        results.append(("CASH: label 'Bukti Pembayaran Lunas'",
                        "Bukti Pembayaran Lunas" in txt))
        results.append(("CASH: bantuan menyebut 'lunas'",
                        "lunas" in txt.lower()))
        # Fill + submit (no KTP for cash)
        pg.fill("#wa_nama", "Tester Cash")
        pg.fill("#wa_no_telepon", "081298880002")
        pg.fill("#wa_alamat", "Jl. Cash No. 2")
        pg.set_input_files("#wa_bukti", IMG)
        pg.fill('input[name="nama_pengirim"]', "Tester Cash")
        pg.fill('input[name="bank_pengirim"]', "BRI")
        pg.click('[form="waPengajuanForm"][type="submit"]')
        pg.wait_for_url("**/akun/pesanan", timeout=45000)
        b.close()

    after_p = int(q("SELECT COUNT(*) FROM pengajuan") or 0)
    after_b = int(q("SELECT COUNT(*) FROM bukti_pembayaran") or 0)
    last_k = q("SELECT b.tipe, b.status, b.nominal FROM bukti_pembayaran b JOIN pengajuan p ON p.id=b.pengajuan_id WHERE p.kode_pesanan LIKE 'MG-%' ORDER BY b.id DESC LIMIT 2")
    print(f"pengajuan {before_p}->{after_p}, bukti {before_b}->{after_b}")
    print("2 bukti terbaru:", last_k)
    print()
    for label, ok in results:
        print(("PASS " if ok else "FAIL ") + label)
    print("\nSCREENSHOTS:", SHOTS)


if __name__ == "__main__":
    main()
