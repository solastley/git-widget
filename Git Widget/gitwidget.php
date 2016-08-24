<?php
return array(
  'title' => 'Git Widget',
  'html'  => function() {
    // any data for the template
    $pull_message = panel()->site()->pull_message();

    $filenames = array();
    $target_1 = 'CONFLICT';
    $target_2 = 'Merge conflict in ';
    $lines = explode(PHP_EOL, $pull_message);
    foreach ($lines as $line) {
        $pos = strpos($line, $target_1);
        if ($pos === false) continue;
        else {
            $parts = explode($target_2, $line);
            $file = panel()->kirby()->roots()->index() . '/' . $parts[1];
            $filenames[] = $file;
        }
    }

    $conflict_status = false;
    $conflicts = array();

    if (count($filenames) !== 0) {
        $conflict_status = true;

        $temp_index;
        $j = 0;
        foreach($filenames as $filename) {
            array_push($conflicts, array());
            $i = 0;
            $conflict_message = '';
            $lines = file($filename);
            foreach ($lines as $line) {
                $pos = strpos($line, '<<<<<<< HEAD');
                $pos2 = strpos($line, '>>>>>>>');
                if ($pos === false) {
                    $i = $i + 1;
                    continue;
                }
                else {
                    $temp_index = $i + 1;
                    while ($pos2 === false) {
                        $conflict_message .= $lines[$i];
                        $i = $i + 1;
                        $pos2 = strpos($lines[$i], '>>>>>>>');
                    }
                    $conflict_message .= $lines[$i];
                    array_push($conflicts[$j], $conflict_message);
                    $conflict_message = '';
                    $i = $temp_index;
                    continue;
                }
            }
            $j = $j + 1;
        }
    }
    $data = array(
        'conflict_status' => $conflict_status,
        'filenames' => $filenames,
        'conflicts' => $conflicts,
        'push_status' => panel()->site()->push_status()
    );
    return tpl::load(__DIR__ . DS . 'gittemplate.php', $data);
  }
);
?>
