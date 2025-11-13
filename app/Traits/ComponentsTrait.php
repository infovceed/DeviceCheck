<?php

namespace App\Traits;

trait ComponentsTrait
{
    public function badge(array $data = []): string
    {
        return view('components.badges', $data);
    }
}
