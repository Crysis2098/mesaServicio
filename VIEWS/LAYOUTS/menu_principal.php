<div class="headeringo">
    <?php foreach ($menu_botones as $boton): ?>
        <button onclick="window.location.href='<?php echo htmlspecialchars($boton['url']); ?>'">
            <?php echo htmlspecialchars($boton['icono'] . ' ' . $boton['label']); ?>
        </button>
    <?php endforeach; ?>

    <span class="usuario_headeringo">
        Usuario: <?php echo (int)$id_empleado; ?><?php echo ' · ' . htmlspecialchars(ucfirst($tipo_usuario)); ?>
    </span>
</div>
