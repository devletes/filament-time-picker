<?php

namespace Devletes\TimePicker\Assets;

use Filament\Support\Assets\Css;

/** Versions by content hash: a path repository's package version never changes, so edits would stay cached. */
class HashedCss extends Css
{
    public function getVersion(): string
    {
        $path = $this->getPath();

        if (filled($path) && ! $this->isRemote() && is_file($path)) {
            return md5_file($path) ?: parent::getVersion();
        }

        return parent::getVersion();
    }
}
