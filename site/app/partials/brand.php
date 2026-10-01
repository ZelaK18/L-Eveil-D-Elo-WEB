<?php /** @var string $brandHref @var string $brandLabel */ ?>
<a href="<?= e($brandHref) ?>" class="brand" aria-label="<?= e(config('site.name') . ', ' . $brandLabel) ?>">
  <span class="mark brand__mark" role="img" aria-label="Logo <?= e(config('site.name')) ?>"></span>
  <span class="brand__name"><?= e(config('site.name')) ?></span>
</a>
