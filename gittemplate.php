<!-- JavaScript for widget (does not have normal header) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    $(document).ready(function(){
        var messages = [];
        $('.conflict-message').each(function(index) {
            var message = $(this).html();
            messages.push(message);
            var new_message = message.split('\n').join('<br />');
            $(this).html(new_message + '<br /><br />');
        });
    });
</script>

<!-- html for the widget -->
<style>
    h2 {
        padding-bottom: 16px;
    }
    .good-merge {
        color: green;
    }
    .bad-merge {
        color: red;
    }
    form {
        margin-left: auto;
        margin-right: auto;
        width: 95%;
    }
    textarea {
        display: none;
    }
    #hidden-filename {
        display: none;
    }
    #hidden-conflict {
        display: none;
    }
    #submit-btn {
        margin-top: 12px;
        padding: 4px 8px;
    }
</style>
<?php if ($conflict_status): ?>
    <h2 class="bad-merge">Merge conflict(s) found:</h2>
    <form action="<?= page('gitwidget')->url() ?>" method="post" id="conflict-form">
        <?php foreach ($conflicts as $file_conflicts): ?>
            <?php foreach ($file_conflicts as $conflict): ?>
                <div class="conflict-message"><?= $conflict ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <input name="conflicts" id="hidden-conflict" value='<?php echo base64_encode(serialize($conflicts)) ?>'/>
        <input name="filenames" id="hidden-filename" value='<?php echo base64_encode(serialize($filenames)) ?>'/>
        <input type="submit" name="submit" id="submit-btn" value="Click here to fix" />
    </form>
<?php elseif ($push_status != '0'): ?>
    <h2 class="bad-merge">An error occurred while updating the Git repository.<br />
        Your changes were NOT merged into the source repository.</h2>
<?php else: ?>
    <h2 class="good-merge">No merge conflicts found.</h2>
    <h2 class="good-merge">No errors thrown.</h2>
<?php endif; ?>
