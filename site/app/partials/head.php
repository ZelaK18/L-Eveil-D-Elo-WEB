<?php /** @var string $title @var string $description */ ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="icon" type="image/png" sizes="180x180" href="images/favicon.png">
<link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
<link rel="stylesheet" href="<?= e(asset($stylesheet ?? 'css/style.css')) ?>">
