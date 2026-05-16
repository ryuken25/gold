<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DownloadMahenGoldAssets extends BaseCommand
{
    protected $group = 'Assets';

    protected $name = 'assets:download-mahengold';

    protected $description = 'Downloads legal/free MahenGold demo assets and creates local SVG fallbacks.';

    protected $usage = 'assets:download-mahengold [--force]';

    protected $options = [
        '--force' => 'Overwrite existing MahenGold assets.',
    ];

    private string $targetDir;

    public function run(array $params)
    {
        helper('filesystem');

        $force = array_key_exists('force', $params) || (bool) CLI::getOption('force');
        $this->targetDir = FCPATH . 'assets/images/mahengold/';

        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0775, true);
        }

        $credits = [
            '# MahenGold Asset Credits',
            '',
            'Gambar digunakan sebagai asset demo website MahenGold.',
            'Sumber dipilih dari penyedia asset gratis/legal seperti Unsplash atau fallback SVG custom lokal.',
            'Tidak menggunakan GPT Image / OpenAI Images API, tidak hotlink, dan semua asset disimpan lokal.',
            '',
        ];

        foreach ($this->downloadableAssets() as $asset) {
            $result = $this->ensureRemoteOrFallback($asset, $force);
            $credits[] = '## ' . $asset['filename'];
            $credits[] = 'Source: ' . $result['source'];
            $credits[] = 'Photographer: ' . $result['photographer'];
            $credits[] = 'License: ' . $result['license'];
            $credits[] = 'Downloaded at: ' . $result['downloaded_at'];
            $credits[] = 'Local fallback: ' . $asset['fallback'];
            $credits[] = 'Status: ' . $result['status'];
            $credits[] = '';
        }

        foreach ($this->svgOnlyAssets() as $asset) {
            $this->writeSvg($asset['filename'], $asset['svg'], $force);
            $credits[] = '## ' . $asset['filename'];
            $credits[] = 'Source: Generated locally as custom SVG placeholder.';
            $credits[] = 'Photographer: MahenGold custom SVG.';
            $credits[] = 'License: Project-owned custom SVG fallback.';
            $credits[] = 'Downloaded at: ' . date('c');
            $credits[] = 'Status: generated locally.';
            $credits[] = '';
        }

        file_put_contents($this->targetDir . 'ASSET_CREDITS.md', implode(PHP_EOL, $credits));
        CLI::write('MahenGold assets are ready in public/assets/images/mahengold/', 'green');
        CLI::write('Credits written to public/assets/images/mahengold/ASSET_CREDITS.md', 'green');
    }

    private function ensureRemoteOrFallback(array $asset, bool $force): array
    {
        $target = $this->targetDir . $asset['filename'];
        $fallbackTarget = $this->targetDir . $asset['fallback'];

        $this->writeSvg($asset['fallback'], $asset['svg'], $force);

        if (!$force && is_file($target)) {
            CLI::write('Skipped existing ' . $asset['filename'], 'yellow');

            return [
                'source' => $asset['source'],
                'photographer' => $asset['photographer'],
                'license' => $asset['license'],
                'downloaded_at' => date('c'),
                'status' => 'existing local file kept. Use --force to overwrite.',
            ];
        }

        foreach ($asset['urls'] as $url) {
            CLI::write('Downloading ' . $asset['filename'] . '...', 'cyan');
            if ($this->downloadFile($url, $target)) {
                return [
                    'source' => $asset['source'],
                    'photographer' => $asset['photographer'],
                    'license' => $asset['license'],
                    'downloaded_at' => date('c'),
                    'status' => 'downloaded to local project.',
                ];
            }
        }

        CLI::write('Download failed for ' . $asset['filename'] . '; fallback SVG is available.', 'yellow');

        return [
            'source' => 'Generated locally as custom SVG placeholder after remote download failed.',
            'photographer' => 'MahenGold custom SVG.',
            'license' => 'Project-owned custom SVG fallback.',
            'downloaded_at' => date('c'),
            'status' => 'remote download failed; using local fallback ' . basename($fallbackTarget) . '.',
        ];
    }

    private function downloadFile(string $url, string $target): bool
    {
        $data = false;
        $contentType = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'MahenGold CI4 asset downloader (+local demo assets)',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $data = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($statusCode >= 400) {
                $data = false;
            }
        }

        if ($data === false) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 25,
                    'follow_location' => 1,
                    'header' => "User-Agent: MahenGold CI4 asset downloader\r\n",
                ],
            ]);
            $data = @file_get_contents($url, false, $context);
        }

        if (!is_string($data) || strlen($data) < 2048) {
            return false;
        }

        if ($contentType !== '' && !str_contains(strtolower($contentType), 'image')) {
            return false;
        }

        return file_put_contents($target, $data) !== false;
    }

    private function writeSvg(string $filename, string $svg, bool $force): void
    {
        $target = $this->targetDir . $filename;
        if (!$force && is_file($target)) {
            return;
        }

        file_put_contents($target, $svg);
    }

    private function downloadableAssets(): array
    {
        $unsplashLicense = 'Unsplash License - free to use, downloaded and stored locally. See https://unsplash.com/license';

        return [
            [
                'filename' => 'hero-gold.jpg',
                'fallback' => 'hero-gold.svg',
                'urls' => ['https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1800&q=88'],
                'source' => 'Unsplash CDN direct asset for luxury gold jewelry visual. Source/photo page: https://unsplash.com/s/photos/gold-jewelry-dark-background',
                'photographer' => 'Unsplash contributor, see source search/photo result page.',
                'license' => $unsplashLicense,
                'svg' => $this->heroSvg(),
            ],
            [
                'filename' => 'product-ring.jpg',
                'fallback' => 'product-ring.svg',
                'urls' => ['https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=1000&q=85'],
                'source' => 'Unsplash CDN direct asset for gold ring jewelry. Source/photo page: https://unsplash.com/s/photos/gold-ring-jewelry',
                'photographer' => 'Unsplash contributor, see source search/photo result page.',
                'license' => $unsplashLicense,
                'svg' => $this->productSvg('Ring Emas', 'ring'),
            ],
            [
                'filename' => 'product-necklace.jpg',
                'fallback' => 'product-necklace.svg',
                'urls' => ['https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=1000&q=85'],
                'source' => 'Unsplash CDN direct asset for elegant gold necklace. Source/photo page: https://unsplash.com/s/photos/gold-necklace-jewelry',
                'photographer' => 'Unsplash contributor, see source search/photo result page.',
                'license' => $unsplashLicense,
                'svg' => $this->productSvg('Kalung Emas', 'necklace'),
            ],
            [
                'filename' => 'product-earrings.jpg',
                'fallback' => 'product-earrings.svg',
                'urls' => ['https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=1000&q=85'],
                'source' => 'Unsplash CDN direct asset for elegant jewelry close-up. Source/photo page: https://unsplash.com/s/photos/gold-earrings-jewelry',
                'photographer' => 'Unsplash contributor, see source search/photo result page.',
                'license' => $unsplashLicense,
                'svg' => $this->productSvg('Anting Emas', 'earrings'),
            ],
            [
                'filename' => 'gold-bars.jpg',
                'fallback' => 'gold-bars.svg',
                'urls' => ['https://images.unsplash.com/photo-1610375461246-83df859d849d?auto=format&fit=crop&w=1200&q=85'],
                'source' => 'Unsplash CDN direct asset for gold bars / bullion. Source/photo page: https://unsplash.com/s/photos/gold-bars',
                'photographer' => 'Unsplash contributor, see source search/photo result page.',
                'license' => $unsplashLicense,
                'svg' => $this->goldBarsSvg(),
            ],
        ];
    }

    private function svgOnlyAssets(): array
    {
        return [
            ['filename' => 'logo-mg.svg', 'svg' => $this->logoSvg()],
            ['filename' => 'admin-dashboard.svg', 'svg' => $this->adminDashboardSvg()],
            ['filename' => 'whatsapp-contact.svg', 'svg' => $this->whatsappSvg()],
        ];
    }

    private function logoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img" aria-label="MahenGold MG logo">
  <defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#FFF2A9"/><stop offset=".45" stop-color="#E7BE55"/><stop offset="1" stop-color="#B98213"/></linearGradient><filter id="s" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="26" stdDeviation="28" flood-color="#140A05" flood-opacity=".45"/></filter></defs>
  <rect width="512" height="512" rx="156" fill="#140A05"/>
  <circle cx="256" cy="256" r="188" fill="url(#g)" filter="url(#s)"/>
  <circle cx="184" cy="160" r="42" fill="#FFF9EF" opacity=".75"/>
  <text x="256" y="298" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="132" font-weight="900" fill="#140A05" letter-spacing="-10">MG</text>
</svg>
SVG;
    }

    private function heroSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMid slice">
  <defs><radialGradient id="r" cx="72%" cy="34%" r="52%"><stop offset="0" stop-color="#F3D37A" stop-opacity=".82"/><stop offset=".46" stop-color="#B98213" stop-opacity=".42"/><stop offset="1" stop-color="#0F0703" stop-opacity="0"/></radialGradient><linearGradient id="b" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#0F0703"/><stop offset=".52" stop-color="#1C1008"/><stop offset="1" stop-color="#3A2412"/></linearGradient></defs>
  <rect width="1600" height="1000" fill="url(#b)"/><rect width="1600" height="1000" fill="url(#r)"/>
  <g transform="translate(890 250) rotate(-14)"><ellipse cx="0" cy="170" rx="250" ry="250" fill="none" stroke="#F3D37A" stroke-width="74" opacity=".92"/><ellipse cx="0" cy="170" rx="150" ry="150" fill="none" stroke="#FFF9EF" stroke-width="10" opacity=".22"/><circle cx="-86" cy="-70" r="86" fill="#FFF9EF" opacity=".75"/></g>
  <g transform="translate(370 620) rotate(14)"><rect x="0" y="0" width="420" height="115" rx="22" fill="#E7BE55" opacity=".92"/><rect x="70" y="-86" width="420" height="115" rx="22" fill="#D4A12A" opacity=".9"/><rect x="140" y="-172" width="420" height="115" rx="22" fill="#B98213" opacity=".9"/></g>
</svg>
SVG;
    }

    private function productSvg(string $label, string $shape): string
    {
        $jewel = match ($shape) {
            'necklace' => '<path d="M210 250c35 120 255 120 290 0" fill="none" stroke="#F3D37A" stroke-width="34" stroke-linecap="round"/><circle cx="355" cy="383" r="42" fill="#FFF2A9"/><path d="M355 330l36 61h-72z" fill="#B98213"/>',
            'earrings' => '<circle cx="285" cy="260" r="74" fill="none" stroke="#F3D37A" stroke-width="34"/><circle cx="435" cy="260" r="74" fill="none" stroke="#F3D37A" stroke-width="34"/><circle cx="285" cy="175" r="24" fill="#FFF9EF"/><circle cx="435" cy="175" r="24" fill="#FFF9EF"/>',
            default => '<circle cx="360" cy="295" r="120" fill="none" stroke="#F3D37A" stroke-width="46"/><circle cx="308" cy="202" r="42" fill="#FFF9EF" opacity=".82"/><path d="M360 130l58 70h-116z" fill="#B98213"/>',
        };

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 540" preserveAspectRatio="xMidYMid slice">
  <defs><linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#FFF9EF"/><stop offset=".5" stop-color="#E7BE55"/><stop offset="1" stop-color="#140A05"/></linearGradient><radialGradient id="glow" cx="40%" cy="30%" r="50%"><stop offset="0" stop-color="#FFF9EF" stop-opacity=".9"/><stop offset="1" stop-color="#FFF9EF" stop-opacity="0"/></radialGradient></defs>
  <rect width="720" height="540" fill="url(#bg)"/><rect width="720" height="540" fill="url(#glow)" opacity=".55"/><g filter="drop-shadow(0 28px 36px rgba(20,10,5,.36))">{$jewel}</g>
  <text x="42" y="488" font-family="Inter, Arial, sans-serif" font-size="42" font-weight="900" fill="#FFF9EF">{$label}</text>
</svg>
SVG;
    }

    private function goldBarsSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 620" preserveAspectRatio="xMidYMid slice">
  <defs><linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#140A05"/><stop offset="1" stop-color="#3A2412"/></linearGradient><linearGradient id="gold" x1="0" x2="1"><stop offset="0" stop-color="#FFF2A9"/><stop offset=".55" stop-color="#D4A12A"/><stop offset="1" stop-color="#B98213"/></linearGradient></defs>
  <rect width="900" height="620" fill="url(#bg)"/><circle cx="715" cy="120" r="230" fill="#E7BE55" opacity=".16"/>
  <g transform="translate(180 345) rotate(-8)" filter="drop-shadow(0 26px 35px rgba(0,0,0,.38))"><rect width="360" height="106" rx="18" fill="url(#gold)"/><rect x="70" y="-88" width="360" height="106" rx="18" fill="url(#gold)"/><rect x="140" y="-176" width="360" height="106" rx="18" fill="url(#gold)"/><text x="180" y="66" text-anchor="middle" font-size="34" font-family="Inter,Arial" font-weight="900" fill="#140A05">GOLD</text></g>
</svg>
SVG;
    }

    private function adminDashboardSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 620">
  <defs><linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#0F0703"/><stop offset="1" stop-color="#2A190D"/></linearGradient><linearGradient id="gold" x1="0" x2="1"><stop offset="0" stop-color="#F3D37A"/><stop offset="1" stop-color="#B98213"/></linearGradient></defs>
  <rect width="900" height="620" rx="44" fill="url(#bg)"/><rect x="96" y="90" width="708" height="440" rx="36" fill="#FFF9EF" opacity=".96"/><rect x="130" y="130" width="150" height="360" rx="24" fill="#1C1008"/><rect x="310" y="132" width="180" height="116" rx="24" fill="#F8F0E2"/><rect x="520" y="132" width="240" height="116" rx="24" fill="#F8F0E2"/><rect x="310" y="282" width="450" height="190" rx="24" fill="#F8F0E2"/><path d="M345 420l82-78 74 54 112-116 100 86" fill="none" stroke="url(#gold)" stroke-width="22" stroke-linecap="round" stroke-linejoin="round"/><circle cx="205" cy="180" r="44" fill="url(#gold)"/></svg>
SVG;
    }

    private function whatsappSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 620">
  <defs><linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#FFF9EF"/><stop offset="1" stop-color="#EFE2C9"/></linearGradient><linearGradient id="wa" x1="0" x2="1"><stop offset="0" stop-color="#19B85A"/><stop offset="1" stop-color="#0F9344"/></linearGradient><linearGradient id="gold" x1="0" x2="1"><stop offset="0" stop-color="#F3D37A"/><stop offset="1" stop-color="#B98213"/></linearGradient></defs>
  <rect width="900" height="620" rx="44" fill="url(#bg)"/><circle cx="660" cy="175" r="155" fill="url(#gold)" opacity=".3"/><rect x="150" y="105" width="470" height="390" rx="38" fill="#140A05"/><rect x="190" y="155" width="390" height="74" rx="24" fill="#FFF9EF" opacity=".94"/><rect x="190" y="255" width="310" height="74" rx="24" fill="#FFF9EF" opacity=".78"/><circle cx="620" cy="380" r="92" fill="url(#wa)"/><path d="M580 418l18-62c15-36 60-42 88-14s23 73-14 88l-62 18z" fill="none" stroke="#fff" stroke-width="22" stroke-linecap="round" stroke-linejoin="round"/></svg>
SVG;
    }
}
