<?php require __DIR__ . '/layout-start.php'; ?>

    <section class="heading-row manage-heading">
      <div>
        <div class="eyebrow">Workspace · <?= escape($roleLabel) ?></div>
        <h1><?= escape($pageTitle) ?></h1>
        <p class="subtitle"><?= escape($pageDescription) ?></p>
      </div>
    </section>

    <?php if ($flash !== null): ?>
      <div class="manage-alert <?= $flash['type'] === 'error' ? 'is-error' : '' ?>" role="status"><?= escape($flash['message']) ?></div>
    <?php endif; ?>
