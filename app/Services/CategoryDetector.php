<?php

namespace App\Services;

class CategoryDetector
{
    private const KEYWORDS = [
        'ILM' => [
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

    public function detect(string $title, ?string $description = null): array
    {
        $text = mb_strtolower(trim($title.' '.($description ?? '')));
        $scores = array_fill_keys(array_keys(self::KEYWORDS), 0);
        $matches = [];

        foreach (self::KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword => $weight) {
                if (str_contains($text, $keyword)) {
                    $scores[$category] += $weight;
                    $matches[$category][] = $keyword;
                }
            }
        }

        arsort($scores);
        $category = array_key_first($scores);
        $topScore = $scores[$category];
        $category = $topScore > 0 ? $category : 'News';

        return [
            'category' => $category,
            'confidence' => $topScore >= 8 ? 'tinggi' : ($topScore >= 4 ? 'sedang' : 'rendah'),
            'reason' => $topScore > 0
                ? 'Kata kunci: '.implode(', ', array_slice($matches[$category] ?? [], 0, 4))
                : 'Tidak ditemukan kata kunci khusus; kategori bawaan News.',
        ];
    }
}
