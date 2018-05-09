<?php

// 读取csv
function read_csv_lines($csv_file = '', $lines = 0, $offset = 0){
    $row = 1;
    $result = [];

    if (($handle = fopen("$csv_file", "r")) !== FALSE) {
        $row = 0;
        while (($data = fgetcsv($handle)) !== FALSE) {
            // 首列作为key
            if ($row == 0) {
                $keys = $data;
            }
            else {
                $tmp = [];

                foreach ($data as $k => $value) {
                    if (!empty($keys[$k])) {
                        $tmp[$keys[$k]] = $value;
                    }
                }

                $result[] = $tmp;
            }

            $row++;
        }
        fclose($handle);
    }

    return $result;
}