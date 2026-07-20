<?php

namespace App\Services;

class CategoryDetector
{
    private const KEYWORDS = [
        'Iklan Layanan Masyarakat' => [
            'iklan layanan masyarakat' => 8, 'ilm' => 8, 'himbauan' => 5,
            'imbauan' => 5, 'ayo' => 3, 'mari' => 3, 'cegah' => 3,
            'waspada' => 3, 'jangan' => 2, 'keselamatan' => 3,
            'kesehatan' => 2, 'stunting' => 4, 'narkoba' => 4,
            'sampah' => 2, 'tertib' => 2, 'layanan publik' => 3,
        ],
        'Program' => [
            'program' => 5, 'episode' => 5, 'eps' => 4, 'talkshow' => 5,
            'podcast' => 5, 'magazine' => 4, 'feature' => 3, 'dialog' => 3,
            'acara' => 3, 'rubrik' => 4, 'serial' => 4, 'live' => 2,
            'kuliner' => 2, 'wisata' => 2,
        ],
        'News' => [
            'berita' => 5, 'news' => 5, 'liputan' => 4, 'reportase' => 5,
            'peristiwa' => 3, 'kegiatan' => 2, 'pemkot' => 2,
            'pemerintah kota' => 3, 'wali kota' => 3, 'dinas' => 2,
            'rapat' => 2, 'kunjungan' => 2, 'pelantikan' => 3,
            'konferensi pers' => 4, 'hari ini' => 2,
        ],
    ];

    private const ISSUE_KEYWORDS = [
        'Ekonomi' => [
            'ekonomi' => 6, 'umkm' => 5, 'usaha' => 4, 'pasar' => 4,
            'harga' => 4, 'inflasi' => 5, 'pajak' => 4, 'anggaran' => 3,
            'investasi' => 4, 'perdagangan' => 4, 'biaya' => 2, 'pendapatan' => 3,
        ],
        'Lingkungan' => [
            'lingkungan' => 6, 'sampah' => 5, 'banjir' => 5, 'pohon' => 3,
            'penghijauan' => 5, 'air bersih' => 4, 'bersih' => 2, 'daur ulang' => 4,
            'konservasi' => 4, 'hijau' => 2, 'emisi' => 4, 'cuaca' => 2,
        ],
        'Sosial' => [
            'sosial' => 6, 'masyarakat' => 5, 'warga' => 4, 'bantuan' => 4,
            'kemiskinan' => 5, 'disabilitas' => 4, 'komunitas' => 4, 'keluarga' => 2,
            'anak' => 2, 'lansia' => 3, 'kesejahteraan' => 4, 'relawan' => 3,
        ],
    ];

    public function detect(string $title, ?string $description = null): array
    {
        $text = mb_strtolower(trim($title.' '.($description ?? '')));
        [$category, $categoryScore, $categoryReason] = $this->detectBestMatch($text, self::KEYWORDS, 'News', 'kategori');
        [$issue, $issueScore, $issueReason] = $this->detectBestMatch($text, self::ISSUE_KEYWORDS, 'Sosial', 'issue');

        return [
            'category' => $category,
            'confidence' => $categoryScore >= 8 ? 'tinggi' : ($categoryScore >= 4 ? 'sedang' : 'rendah'),
            'reason' => $categoryReason,
            'issue' => $issue,
            'issue_confidence' => $issueScore >= 8 ? 'tinggi' : ($issueScore >= 4 ? 'sedang' : 'rendah'),
            'issue_reason' => $issueReason,
        ];
    }

    private function detectBestMatch(string $text, array $keywordsByGroup, string $default, string $label): array
    {
        $scores = array_fill_keys(array_keys($keywordsByGroup), 0);
        $matches = [];

        foreach ($keywordsByGroup as $group => $keywords) {
            foreach ($keywords as $keyword => $weight) {
                if (str_contains($text, $keyword)) {
                    $scores[$group] += $weight;
                    $matches[$group][] = $keyword;
                }
            }
        }

        arsort($scores);
        $group = array_key_first($scores);
        $score = $scores[$group];
        $group = $score > 0 ? $group : $default;

        return [
            $group,
            $score,
            $score > 0
                ? ucfirst($label).': '.implode(', ', array_slice($matches[$group] ?? [], 0, 4))
                : 'Tidak ditemukan kata kunci khusus; '.strtolower($label).' bawaan '.$default.'.',
        ];
    }
}
