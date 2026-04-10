<?php
// includes/wizard_nav.php
$current_step = $current_step ?? 1;
$steps = [
    1 => 'Start',
    2 => 'Subjects',
    3 => 'Faculty',
    4 => 'Rooms',
    5 => 'Slots',
    6 => 'Generate'
];
?>
<div class="row mb-4">
    <div class="col-12 text-center position-relative">
        <!-- Progress Line -->
        <div class="position-absolute top-50 start-0 end-0 bg-light" style="height: 4px; z-index: 1; transform: translateY(-50%); margin: 0 5%;">
            <div class="bg-primary" style="height: 100%; width: <?= ($current_step-1) * 20 ?>%; transition: width 0.3s ease;"></div>
        </div>
        <!-- Progress Dots -->
        <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
            <?php foreach($steps as $num => $label): ?>
                <?php 
                    $is_active = ($num == $current_step);
                    $is_passed = ($num < $current_step);
                    $bg_class = $is_active ? 'bg-primary text-white border-primary shadow' : ($is_passed ? 'bg-success text-white border-success' : 'bg-white text-muted border-light');
                ?>
                <div class="text-center bg-white px-2">
                    <div class="rounded-circle <?= $bg_class ?> d-flex align-items-center justify-content-center mx-auto mb-1 border" style="width: 40px; height: 40px; font-weight: 600; border-width: 2px !important;">
                        <?php if($is_passed): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            <?= $num ?>
                        <?php endif; ?>
                    </div>
                    <small class="fw-bold <?= $is_active||$is_passed ? 'text-dark' : 'text-muted' ?>"><?= $label ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
