<?php
// index.php - Landing Page do AgroControl
// Página inicial do sistema

session_start();

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: modules/dashboard/index.php');
    exit;
}

$pageTitle = 'AgroControl - Gestão Rural Inteligente';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary: #28a745;
            --primary-dark: #1e7e34;
            --secondary: #6c757d;
            --dark: #1a2a3a;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar {
            background: rgba(255,255,255,0.95);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 15px 0;
            transition: all 0.3s;
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary) !important;
        }
        
        .navbar-brand i {
            font-size: 2rem;
            margin-right: 10px;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark) !important;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--primary) !important;
        }
        
        .btn-primary-custom {
            background: var(--primary);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        
        .btn-outline-custom {
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            padding: 10px 25px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 70%;
            height: 140%;
            background: linear-gradient(135deg, rgba(40,167,69,0.05) 0%, rgba(40,167,69,0.1) 100%);
            transform: rotate(15deg);
            border-radius: 30px;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .hero h1 span {
            color: var(--primary);
        }
        
        .hero p {
            font-size: 1.2rem;
            color: var(--secondary);
            margin-bottom: 30px;
        }
        
        .hero-stats {
            margin-top: 40px;
            display: flex;
            gap: 30px;
        }
        
        .hero-stat {
            text-align: center;
        }
        
        .hero-stat h3 {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .hero-stat p {
            font-size: 0.9rem;
            margin-bottom: 0;
            color: var(--secondary);
        }
        
        .hero-image {
            position: relative;
            z-index: 1;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        /* Seções */
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 1.1rem;
            color: var(--secondary);
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Cards de Funcionalidades */
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
            border: 1px solid #e9ecef;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(40,167,69,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .feature-icon i {
            font-size: 40px;
            color: var(--primary);
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: var(--secondary);
            line-height: 1.6;
        }
        
        /* Planos */
        .plan-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
            position: relative;
            border: 1px solid #e9ecef;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .plan-card.featured {
            border: 2px solid var(--primary);
            transform: scale(1.05);
        }
        
        .plan-card.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }
        
        .plan-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-price {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .plan-price h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0;
        }
        
        .plan-price span {
            font-size: 1rem;
            color: var(--secondary);
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }
        
        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .plan-features i {
            color: var(--primary);
            margin-right: 10px;
        }
        
        /* Depoimentos */
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .testimonial-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
            color: white;
        }
        
        .testimonial-text {
            font-style: italic;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: var(--dark);
        }
        
        /* CTA */
        .cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 60px 0;
            border-radius: 30px;
            margin: 40px 0;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta .btn-light {
            background: white;
            color: var(--primary);
            padding: 12px 35px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .cta .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer h5 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--primary);
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .footer-social a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .footer-social a:hover {
            background: var(--primary);
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.2rem;
            }
            .hero-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .plan-card.featured {
                transform: scale(1);
            }
            .plan-card.featured:hover {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="bi bi-tree-fill"></i>
            AgroControl
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#funcionalidades">Funcionalidades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#planos">Planos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#depoimentos">Depoimentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contato">Contato</a>
                </li>
            </ul>
            <div class="ms-lg-3 d-flex gap-2">
                <a href="modules/auth/login.php" class="btn btn-outline-custom">Entrar</a>
                <a href="modules/auth/register.php" class="btn btn-primary-custom">Começar Agora</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content" data-aos="fade-right">
                <h1>Gestão rural <span>inteligente</span> para o futuro do campo</h1>
                <p>Controle tudo da sua fazenda em um só lugar: animais, produção, estoque, finanças e muito mais. Tudo de forma simples e eficiente.</p>
                <div class="d-flex gap-3">
                    <a href="modules/auth/register.php" class="btn btn-primary-custom btn-lg">Experimente Grátis</a>
                    <a href="#funcionalidades" class="btn btn-outline-custom btn-lg">Saiba mais</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3>+1.500</h3>
                        <p>Fazendas atendidas</p>
                    </div>
                    <div class="hero-stat">
                        <h3>+50.000</h3>
                        <p>Animais gerenciados</p>
                    </div>
                    <div class="hero-stat">
                        <h3>99%</h3>
                        <p>Satisfação dos clientes</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 hero-image" data-aos="fade-left">
                <img src="https://cdn-icons-png.flaticon.com/512/1998/1998625.png" alt="Dashboard" class="img-fluid" style="max-width: 100%;">
            </div>
        </div>
    </div>
</section>

<!-- Funcionalidades -->
<section id="funcionalidades" class="section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Tudo o que você precisa para gerenciar sua fazenda</h2>
            <p>Ferramentas completas para otimizar sua produção e aumentar a rentabilidade</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-tree"></i>
                    </div>
                    <h3>Gestão de Animais</h3>
                    <p>Controle completo de bovinos, com histórico de pesagens, vacinas e produção de leite.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-cup-fill"></i>
                    </div>
                    <h3>Produção de Leite</h3>
                    <p>Registre produção individual ou total, acompanhe médias e gráficos de desempenho.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3>Controle de Estoque</h3>
                    <p>Gerencie rações, vacinas e insumos com alertas de estoque baixo e vencimento.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3>Financeiro</h3>
                    <p>Controle receitas, despesas, contas a pagar e receber com relatórios completos.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Vacinas e Pesagens</h3>
                    <p>Calendário de vacinas, controle de peso e ganho diário dos animais.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h3>Relatórios e Gráficos</h3>
                    <p>Análises completas com gráficos interativos para tomar as melhores decisões.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Planos -->
<section id="planos" class="section bg-light">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Escolha o plano ideal para sua fazenda</h2>
            <p>Planos flexíveis que se adaptam ao tamanho da sua propriedade</p>
        </div>
        <div class="row g-4 align-items-center">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="plan-card">
                    <h3 class="text-center">Básico</h3>
                    <div class="plan-price">
                        <h2>R$ 49<small>/mês</small></h2>
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Até 100 animais</li>
                        <li><i class="bi bi-check-circle-fill"></i> Controle de produção</li>
                        <li><i class="bi bi-check-circle-fill"></i> Registro de despesas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Relatórios simples</li>
                        <li><i class="bi bi-x-circle-fill text-secondary"></i> Controle de estoque</li>
                    </ul>
                    <div class="text-center">
                        <a href="modules/auth/register.php" class="btn btn-outline-custom">Começar Agora</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="plan-card featured">
                    <div class="plan-badge">Mais Vendido</div>
                    <h3 class="text-center">Produtor</h3>
                    <div class="plan-price">
                        <h2>R$ 79<small>/mês</small></h2>
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Até 500 animais</li>
                        <li><i class="bi bi-check-circle-fill"></i> Controle de produção</li>
                        <li><i class="bi bi-check-circle-fill"></i> Controle de estoque</li>
                        <li><i class="bi bi-check-circle-fill"></i> Histórico da produção</li>
                        <li><i class="bi bi-check-circle-fill"></i> Até 5 usuários</li>
                        <li><i class="bi bi-check-circle-fill"></i> Relatórios completos</li>
                    </ul>
                    <div class="text-center">
                        <a href="modules/auth/register.php" class="btn btn-primary-custom">Começar Agora</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="plan-card">
                    <h3 class="text-center">Fazenda Pro</h3>
                    <div class="plan-price">
                        <h2>R$ 129<small>/mês</small></h2>
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Animais ilimitados</li>
                        <li><i class="bi bi-check-circle-fill"></i> Múltiplas propriedades</li>
                        <li><i class="bi bi-check-circle-fill"></i> Até 15 usuários</li>
                        <li><i class="bi bi-check-circle-fill"></i> Relatórios avançados</li>
                        <li><i class="bi bi-check-circle-fill"></i> Prioridade no suporte</li>
                        <li><i class="bi bi-check-circle-fill"></i> Treinamento personalizado</li>
                    </ul>
                    <div class="text-center">
                        <a href="modules/auth/register.php" class="btn btn-outline-custom">Começar Agora</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Depoimentos -->
<section id="depoimentos" class="section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>O que nossos clientes dizem</h2>
            <p>Produtores que já transformaram sua gestão com o AgroControl</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="testimonial-card text-center">
                    <div class="testimonial-img">
                        <i class="bi bi-person"></i>
                    </div>
                    <p class="testimonial-text">"O AgroControl revolucionou a gestão da minha fazenda. Agora tenho controle total de tudo em um só lugar!"</p>
                    <p class="testimonial-author">João Silva</p>
                    <small>Fazenda Boa Vista - SP</small>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="testimonial-card text-center">
                    <div class="testimonial-img">
                        <i class="bi bi-person"></i>
                    </div>
                    <p class="testimonial-text">"A gestão de produção de leite ficou muito mais fácil. Os gráficos ajudam a tomar decisões mais rápidas."</p>
                    <p class="testimonial-author">Maria Oliveira</p>
                    <small>Fazenda Santa Fé - MG</small>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="testimonial-card text-center">
                    <div class="testimonial-img">
                        <i class="bi bi-person"></i>
                    </div>
                    <p class="testimonial-text">"Controle de estoque e vacinas nunca foi tão simples. Recomendo para todos os produtores!"</p>
                    <p class="testimonial-author">Carlos Santos</p>
                    <small>Fazenda Esperança - PR</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="cta" data-aos="zoom-in">
            <h2>Pronto para transformar sua gestão rural?</h2>
            <p>Comece agora mesmo e tenha controle total da sua fazenda</p>
            <a href="modules/auth/register.php" class="btn btn-light btn-lg mt-3">Começar Agora - Grátis por 30 dias!</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="contato" class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="bi bi-tree-fill me-2"></i>AgroControl</h5>
                <p>O sistema de gestão rural mais completo do Brasil. Tecnologia que transforma o campo.</p>
                <div class="footer-social">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-whatsapp"></i></a>
                    <a href="#"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Produto</h5>
                <ul class="list-unstyled">
                    <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <li><a href="#planos">Planos</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Empresa</h5>
                <ul class="list-unstyled">
                    <li><a href="#">Sobre nós</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Carreiras</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Contato</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-envelope me-2"></i> contatobytesolutions25@gmail.com
</li>
                    <li><i class="bi bi-telephone me-2"></i> ((33)99855-7104)</li>
                    <li><i class="bi bi-clock me-2"></i> Segunda a Sexta, 8h às 18h</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> AgroControl. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.padding = '10px 0';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        } else {
            navbar.style.padding = '15px 0';
            navbar.style.boxShadow = 'none';
        }
    });
</script>
</body>
</html>