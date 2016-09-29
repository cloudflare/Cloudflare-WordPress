<?php

namespace CF\WordPress;

class WordPressWrapper
{
    public function fileGetContents($input)
    {
        return file_get_contents($input);
    }
}
