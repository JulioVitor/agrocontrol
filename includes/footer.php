<?php
// includes/footer.php
// Rodapé padrão do sistema
?>

    </div> <!-- Fecha #content -->
</div> <!-- Fecha .wrapper -->

<!-- Modal de Ajuda -->
<div class="modal fade" id="ajudaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle me-2"></i>
                    Central de Ajuda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Precisa de ajuda com o sistema?</p>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-envelope me-2 text-success"></i>
                        suporte@agrocontrol.com
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-whatsapp me-2 text-success"></i>
                        (11) 99999-9999
                    </li>
                    <li>
                        <i class="bi bi-clock me-2 text-success"></i>
                        Segunda a Sexta, 8h às 18h
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js (para gráficos) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<!-- JavaScript Personalizado -->
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>

<script>
$(document).ready(function() {
    // Toggle sidebar no mobile
    $('#sidebarCollapse, #sidebarCollapseBtn').on('click', function() {
        $('#sidebar').toggleClass('active');
    });
    
    // Fechar sidebar ao clicar fora no mobile
    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#sidebar').length && !$(e.target).closest('#sidebarCollapse').length && !$(e.target).closest('#sidebarCollapseBtn').length) {
                $('#sidebar').removeClass('active');
            }
        }
    });
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-fechar alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>


<!-- Botão de instalação do PWA -->
<div class="position-fixed bottom-0 start-0 w-100 p-2 d-none" id="installPwaContainer" style="z-index: 1000;">
    <div class="d-flex justify-content-between align-items-center bg-success text-white p-3 rounded-3 shadow">
        <div>
            <i class="bi bi-download fs-4 me-2"></i>
            <strong>Instalar App</strong>
            <small class="d-block">Instale o AgroControl no seu celular</small>
        </div>
        <button id="installPwaBtn" class="btn btn-light btn-sm">
            Instalar <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</div>

<script>
// Mostrar container de instalação se o PWA não estiver instalado
if (window.matchMedia('(display-mode: standalone)').matches) {
    document.getElementById('installPwaContainer').style.display = 'none';
} else {
    // Mostrar após alguns segundos
    setTimeout(() => {
        const container = document.getElementById('installPwaContainer');
        if (container && !localStorage.getItem('pwa-install-dismissed')) {
            container.style.display = 'block';
            
            // Botão de fechar
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            closeBtn.className = 'btn-close btn-close-white ms-2';
            closeBtn.style.position = 'absolute';
            closeBtn.style.top = '10px';
            closeBtn.style.right = '15px';
            closeBtn.addEventListener('click', () => {
                container.style.display = 'none';
                localStorage.setItem('pwa-install-dismissed', 'true');
            });
            container.querySelector('.d-flex').appendChild(closeBtn);
        }
    }, 5000);
}
</script>


</body>
</html>