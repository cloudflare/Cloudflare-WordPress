<?php

namespace CF\WordPress;

class WordPressWrapper
{
    public function getOption($key, $default)
    {
        return get_option($key, $default);
    }

    public function updateOption($key, $value)
    {
        return update_option($key, $value);
    }

    public function deleteOption($key)
    {
        return delete_option($key);
    }

    public function getSiteURL()
    {
        return get_site_url();
    }
}
