<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Helper;

class DocumentsHelper extends Helper {

    /**
     * Retrieve Document's Variables
     *
     * @param string $string
     * @return array
     */
    public function vars(string $string): array
    {
        // Regular expression to match the variables in the format %VAR%
        $pattern = '/{{([^}]+)}}/';

        // Find all matches
        preg_match_all($pattern, $string, $matches);

        // The variables are in the second element of the $matches array
        $variables = array_unique($matches[0]);

        // Return the variables
        return $variables;
    }

    /**
     * Replace Variables from an array
     *
     * @param string $string
     * @param array $values
     * @return string
     */
    public function replace(string $string, array $values): string
    {
        // Replace the placeholders with the corresponding data
        foreach ($values as $key => $value) {
            $string = str_replace('{{' . $key . '}}', $value ?? '', $string);
        }

        // Return the string
        return $string;
    }
}
