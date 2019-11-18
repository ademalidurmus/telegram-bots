<?php namespace AAD\TelegramBots\Helper;

class Language
{
    protected static $languages;

    public static function get($info)
    {
        $exp = explode("::", $info, 3);
        $language = str_replace(" ", "_", (string) $exp[0]);
        $identifier = str_replace(" ", "_", (string) $exp[1]);
        $default = $exp[2] ?? '';

        if (!isset(self::$languages[$language]) || !isset(self::$languages[$language][$identifier])) {
            return $default;
        }

        return self::$languages[$language][$identifier];
    }

    public static function set(array $info, $identifier)
    {
        $default = '';
        $identifier = str_replace(" ", "_", (string) $identifier);

        foreach ($info as $index => $item) {
            $exp = explode("::", $item, 3);
            $language = str_replace(" ", "_", (string) $exp[0]);
            $text = $exp[1] ?? '';

            if ($index === 0 || substr($language, 0, 1) === "!") {
                $language = ltrim($language, "!");
                $default = $text;
            }

            if (!isset(self::$languages[$language])) {
                self::$languages[$language] = [];
            }

            self::$languages[$language][$identifier] = $text;
        }

        return $default;
    }
}
