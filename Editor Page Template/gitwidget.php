<!-- Written by Solomon Astley, at MAYA Design, 2016 -->
<!-- This code receives merge conflicts from the git widget and allows
    the user to edit them and change the corresponding file -->

<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        // POST coming from git widget on dashboard. 'conflicts' is an array of arrays,
        // each containing all of the merge conflicts from each filename. 'filenames' is
        // an array of the corresponding filenames
        if (isset($_POST['conflicts'])) { $messages = unserialize(base64_decode($_POST['conflicts'])); };
        if (isset($_POST['filenames'])) { $filenames = unserialize(base64_decode($_POST['filenames'])); };

        // POST coming from this same page after the 'submit' button is clicked
        // 'fixed-conflicts' is an array of arrays, each containing all of the
        // user-edited conflicts. 'fixed-filenames' is again just an array of the
        // corresponding filenames.
        if (isset($_POST['fixed-conflicts'])) {
            $fixed_conflicts = $_POST['fixed-conflicts'];
            $filenames = $_POST['fixed-filenames'];

            // Some initializations so no PHP notices are error-logged
            $first_line;
            $last_line;
            $j = 0;

            // Loop through the files containing the merge conflicts
            foreach ($filenames as $filename) {

                // $first_line and $last_line are arrays which will contain the
                // corresponding indices of the first-line and last-line of each
                // merge conflict in a given file. So, if there are three merge
                // conflicts in a file, the length of these arrays will be 3.
                $first_line = array();
                $last_line = array();

                // Split each file into an array of lines and loop through
                $lines = file($filename);
                $i = 0;
                $temp_index;
                foreach($lines as $line) {
                    // For each line of the file, search for the beginning and
                    // end points of a merge conflict
                    $pos = strpos($line, '<<<<<<< HEAD');
                    $pos2 = strpos($line, '>>>>>>>');

                    // If the beginning of a merge conflict is not in a line,
                    // simply continue iterating
                    if ($pos === false) {
                        $i = $i + 1;
                        continue;
                    }

                    // If the beginning of a merge conflict is found:
                    else {
                        $temp_index = $i + 1; // keep track of current $i
                        array_push($first_line, $i); // push $i onto array of first_line indices

                        // while the current line does not contain the end of the
                        // merge conflict, keep iterating
                        while ($pos2 === false) {
                            $i = $i + 1;
                            $pos2 = strpos($lines[$i], '>>>>>>>');
                        }

                        // once the end of a merge conflict has been found, push
                        // $i onto the array of last_line indices
                        array_push($last_line, $i);

                        // redefine $i so it corresponds to where we are in the
                        // foreach loop, then continue iterating through the file
                        // to search for more merge conflicts
                        $i = $temp_index;
                        continue;
                    }
                }

                // open each file to re-write it with the updated text
                $file = fopen($filename, 'w');

                // an array of booleans to keep track of whether or not each merge
                // conflict has been re-written or not
                $written = array();
                array_push($written, false);

                $i = 0;
                $k = 0;
                // loop through lines in the file
                foreach($lines as $line) {

                    // if the current line is before or after the first_line and
                    // last_line indices, respectively, of the current merge conflict,
                    // the line is a good line and is simply re-written to the file
                    // as-is
                    if ($i < $first_line[$k] || $i > $last_line[$k]) {
                        fwrite($file, $line);
                        $i = $i + 1;
                    }

                    // else if the current line is NOT in one of the aforementioned
                    // locations, and also if the current fixed-merge-conflict has not
                    // already been written to the file, then write the fixed-merge-conflict
                    // to the file, and mark it as written
                    else if (!$written[$k]) {
                        fwrite($file, $fixed_conflicts[$j][$k]);
                        $written[$k] = true;
                        $i = $i + 1;
                    }

                    // else if the current line is NOT in one of the aforementioned
                    // locations, and also the current fixed-merge-conflict has been
                    // written to the file, then check whether or not to increment $k
                    // and then simply iterate
                    else {
                        // if the last line of the current merge conflict has been
                        // reached, and there are still fixed-merge-conflicts left
                        // to write, then increment $k
                        if ($i == $last_line[$k] && $k != (count($last_line) - 1)) {
                            $k = $k + 1;
                        }

                        // also increment $i without writing anything to the file
                        // no matter what because we don't want to write anything
                        // from the old, unfixed-merge-conflict
                        $i = $i + 1;
                    }
                }
                // increment $j to keep track of location in $fixed_conflicts array
                $j = $j + 1;
            }

            // Execute the git commands and update the site variables
            git_update();
        }
    }
 ?>

 <!DOCTYPE html>
 <html lang="en">
 <head>

     <meta charset="utf-8">
     <meta http-equiv="X-UA-Compatible" content="IE=edge">
     <meta name="viewport" content="width=device-width, initial-scale=1">
     <meta name="description" content="<?= $site->description() ?>">
     <meta name="author" content="<?= $site->author() ?>">

     <title><?php echo $site->title()->html() ?> | <?php echo $page->title()->html() ?></title>

     <?php echo css('/assets/css/bootstrap.min.css') ?>
     <?php echo css('/assets/css/c3.min.css') ?>
     <?php echo css('/assets/css/clean-blog.css') ?>

     <?php echo js('/assets/js/jquery.min.js') ?>

     <!-- favicon link -->
     <link rel='shortcut icon' type='image/x-icon' href='/assets/images/favicon.ico' />

     <!-- Custom Fonts -->
     <link href="http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
     <link href='http://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
     <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>

     <style>
        textarea {
            width: 50%;
            height: auto;
        }
     </style>
</head>

<body>
    <!-- if the form on this page has not already been submitted: -->
    <?php if (!isset($_POST['fixed-conflicts'])): ?>
        <h3>Please edit and submit the changes to your merge conflict(s):</h3>

        <!-- display the form -->
        <form name="conflict-edit-form" id="conflict-edit-form" method="post">

        <!-- for each file containing a merge conflict: -->
        <?php $i = 0; ?>
        <?php foreach($filenames as $filename): ?>
            <?= $filename ?>
            <br />

            <!-- for each merge conflict in the current file: -->
            <?php $j = 0; ?>
            <?php foreach($messages[$i] as $message): ?>
                <textarea name="fixed-conflicts[<?=$i?>][<?=$j?>]" rows="20" style="width: 50%;" class="new-message"><?= $message ?></textarea>
                <input name="fixed-filenames[<?=$i?>]" class="hidden-filename" style="display: none;" value="<?= $filename ?>"/>
                <br />
            <?php $j = $j + 1; ?>
            <?php endforeach; ?>
        <?php $i = $i + 1; ?>
        <?php endforeach; ?>
            <input type="submit" id="fix-form-submit-btn" />
        </form>

    <!-- if the form on this page was already submitted: -->
    <?php else: ?>
        <h3>Merge conflicts resolved.</h3>
    <?php endif; ?>
</body>
