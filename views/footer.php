<footer class="bg-dark text-white text-center py-3 mt-auto">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> KIV/WEB. Všechna práva vyhrazena.</p>
    </div>
</footer>

<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>