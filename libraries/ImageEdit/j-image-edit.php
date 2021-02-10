<?php
$fieldname = 'image';
$savedir   = isset($_GET['savedir']) ? urldecode($_GET['savedir']) : '';
if (empty($savedir)) {
	$savedir = isset($_POST['savedir']) ? $_POST['savedir'] : '';
}
$savepath  = $_SERVER['DOCUMENT_ROOT'] . $savedir;
$savename  = isset($_GET['savename']) ? $_GET['savename'] : '';
if (empty($savename)) {
	$savename = isset($_POST['savename']) ? $_POST['savename'] : '';
}
$el_id = isset($_GET['el_id']) ? $_GET['el_id'] : '';
if (empty($el_id)) {
	$el_id = isset($_POST['el_id']) ? $_POST['el_id'] : '';
}
$config = array(
	'savepath'  => $savepath,
	'savename'  => $savename,
	#'cache_dir' => $_SERVER['DOCUMENT_ROOT'] . '/cache',
	'max_size' => (4 * 1024 * 1024), // 4 MB is about the max sensible size
);
/*----*/
#echo "<pre>\n"; var_dump($config); echo "</pre>\n";
$step     = 1;
require 'ImageEdit.php';
$imageEdit = new ImageEdit($config);
$imageEdit->run();
$vals = $imageEdit->getValues();
if ($vals['has_img']) {
	$step = 2;
}
if ($vals['saved']) {
	$step = 3;
}
?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Image Editor</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-jasny.min.css">
<link rel="stylesheet" href="css/jquery.Jcrop.min.css">
<link rel="stylesheet" href="css/style.min.css">
</head>
<body>
<div class="container-fluid">
<button type="button" class="close" data-dismiss="modal" aria-hidden="true" onclick="window.parent.SqueezeBox.close();">&times;</button>
	<div class="row-fluid">
		<?php if ($step == 1): ?>
		<h1>Step 1: Choose image</h1>
		<?php if(!empty($vals['errors'])): ?>
		<?php foreach($vals['errors'] as $error): ?>
		<p class="alert alert-error"><?php echo $error; ?></p>
		<?php endforeach; ?>
		<?php endif; ?>
		<form id="upload_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" id="savename" name="savename" value="<?php echo $savename; ?>" />
			<input type="hidden" id="savedir" name="savedir" value="<?php echo $savedir; ?>" />
			<input type="hidden" id="el_id" name="el_id" value="<?php echo $el_id; ?>" />
			<div class="fileupload fileupload-new" data-provides="fileupload">
				<div class="fileupload-new thumbnail" style="width: 200px; height: 150px;"><img src="https://www.placehold.it/200x150/EFEFEF/AAAAAA&text=no+image" /></div>
				<div class="fileupload-preview fileupload-exists thumbnail" style="max-width: 200px; max-height: 150px; line-height: 20px;"></div>
				<div>
					<span class="btn btn-file"><span class="fileupload-new">Select image</span><span class="fileupload-exists">Change</span><input type="file" name="<?php echo $fieldname; ?>" id="fileinput" /></span>
					<a href="#" class="btn fileupload-exists" data-dismiss="fileupload" id="remove">Remove</a>
					<button type="submit" class="btn" id="btn-next">Next</button>
				</div>
			</div>
		</form>
		<?php elseif ($step == 2): ?>
		<h1>Step 2: Edit Image</h1>
		<?php if(!empty($vals['errors'])): ?>
		<?php foreach($vals['errors'] as $error): ?>
		<p class="alert alert-error"><?php echo $error; ?></p>
		<?php endforeach; ?>
		<?php endif; ?>
		<div class="alert  alert-warning">
			<p><strong>Please note:</strong> If your image is not exactly square, you must use the crop tool to crop your image before you can save it.</p>
		</div>
		<form id="edit_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" id="cache_id" name="cache_id" value="<?php echo $vals['cache_id']; ?>" />
			<input type="hidden" id="ext" name="ext" value="<?php echo $vals['ext']; ?>" />
			<input type="hidden" id="max_version" name="max_version" value="<?php echo $vals['max_version']; ?>" />
			<input type="hidden" id="savename" name="savename" value="<?php echo $savename; ?>" />
			<input type="hidden" id="savedir" name="savedir" value="<?php echo $savedir; ?>" />
			<input type="hidden" id="el_id" name="el_id" value="<?php echo $el_id; ?>" />
			<input type="hidden" id="version" name="version" value="<?php echo $vals['version']; ?>" />
			<input type="hidden" id="x" name="x">
			<input type="hidden" id="y" name="y">
			<input type="hidden" id="w" name="w">
			<input type="hidden" id="h" name="h">
			<input type="hidden" id="action" name="action">
			<div id="toolbar" class="btn-toolbar">
				<div class="btn-group btn-toolbar">
					<button type="submit" class="btn btn-primary btn-small" id="btn-undo" data-action="undo"><i class="icon-undo"></i> Undo</button>
					<button type="submit" class="btn btn-primary btn-small" id="btn-redo" data-action="redo"><i class="icon-redo"></i> Redo</button>
				</div>
				<div class="btn-group btn-toolbar">
					<button type="submit" class="btn btn-primary btn-small btn-small" id="btn-anticlockwise" data-action="anticlockwise"><i class="icon-anticlockwise"></i> Rotate Anticlockwise</button>
					<button type="submit" class="btn btn-primary btn-small" id="btn-clockwise" data-action="clockwise"><i class="icon-clockwise"></i> Rotate Clockwise</button>
				</div>
				<div class="btn-group btn-toolbar">
					<button type="submit" class="btn btn-primary btn-small" id="btn-greyscale" data-action="greyscale"><i class="icon-greyscale"></i> Greyscale</button>
					<button type="submit" class="btn btn-primary btn-small" id="btn-sepia" data-action="sepia"><i class="icon-greyscale"></i> Sepia</button>
				</div>
				<div class="btn-group btn-toolbar">
					<button type="submit" class="btn btn-primary btn-small" id="btn-crop" data-action="crop"><i class="icon-crop"></i> Crop</button>
				</div>
				<div class="btn-group btn-toolbar">
					<button type="submit" class="btn btn-success btn-small" id="btn-save" data-action="save"><i class="icon-save"></i> Save</button>
				</div>
			</div>
			<div id="image-editor"><img src="<?php echo $vals['current_img']; ?>" id="editor" /></div>
		</form>
		<?php elseif ($step == 3): ?>
		<h1>Step 3: Done!</h1>
		<p class="alert alert-success">Image successfully saved. All done!</p>
		<script>
		/* Empty src first to force refresh as image name doesn't actually change */
		window.parent.document.getElementById('<?php echo $el_id; ?>-preview').src = "<?php echo $savedir . '/' . $savename . '.jpg?' . time(); ?>";
		window.parent.document.getElementById('<?php echo $el_id; ?>').value = "<?php echo $savedir . '/' . $savename . '.jpg'; ?>";
		</script>
		<?php endif; ?>
	</div>
</div>
<?php /*
<div class="container-fluid">
	<div class="row-fluid">
	<?php
	echo "<pre>\n";var_dump($vals);echo "</pre>\n";
	if (isset($_POST)) {
		echo "<pre>\n"; var_dump($_POST); echo "</pre>\n";
		if (isset($_FILES)) {
			echo "<pre>\n"; var_dump($_FILES); echo "</pre>\n";
		}
	}
	?>
	</div>
</div>
*/ ?>
<script src="js/jquery.min.js"></script>
<script src="js/jquery.Jcrop.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>