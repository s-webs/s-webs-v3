<!doctype html>
<html lang="en">

<head>
    <!-- Meta Data -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Разработка сайтов | S-WEBS</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/favicon-s-webs.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-s-webs.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-s-webs.png">
    <link rel="mask-icon" href="/assets/img/favicon/favicon-s-webs.png" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <!-- Dependency Styles -->
    <link rel="stylesheet" href="/dependencies/bootstrap/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/fontawesome/css/all.min.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/swiper/css/swiper.min.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/wow/css/animate.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/simple-line-icons/css/simple-line-icons.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/themify-icons/css/themify-icons.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/components-elegant-icons/css/elegant-icons.min.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/magnific-popup/css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="/dependencies/slick-carousel/css/slick.css" type="text/css">


    <!-- Site Stylesheet -->
    <link rel="stylesheet" href="/assets/css/app.css" type="text/css">
    <link rel="stylesheet" href="/assets/css/custom.css" type="text/css">

    <!-- Google Web Fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Barlow+Condensed:300,400,500,600,700,800%7CPoppins:300,400,500,600,700,800"
        rel="stylesheet">

</head>

<body id="home-version-1" class="home-agency" data-style="default">

<a href="#main_content" data-type="section-switch" class="return-to-top">
    <i class="fa fa-chevron-up"></i>
</a>

<div class="page-loader">
    <div class="page-loading-wrapper">
        <div class="loading loading07">
            <span data-text="S">S</span>
            <span data-text="-">-</span>
            <span data-text="W">W</span>
            <span data-text="E">E</span>
            <span data-text="B">B</span>
            <span data-text="S">S</span>
        </div>
    </div>
</div>


<div id="main_content" class="main-content">


    <!--=========================-->
    <!--=        Navbar         =-->
    <!--=========================-->
    <header class="site-header header-agency header-transparent header-fixed" data-header-fixed="true"
            data-mobile-menu-resolution="992">
        <div class="container">
            <div class="header-inner">

                <nav id="site-navigation" class="main-nav">

                    <div class="site-logo">
                        <a href="/" class="logo">
                            <img src="/assets/img/logo-main.svg" alt="site logo" class="main-logo"
                                 style="width: 200px;">
                            <img src="/assets/img/logo-main.svg" alt="site logo" class="logo-sticky"
                                 style="width: 200px;">
                        </a>
                    </div>
                    <!-- /.site-logo -->

                    <div class="menu-wrapper main-nav-container canvas-menu-wrapper" id="mega-menu-wrap">
                        <div class="canvas-header">
                            <div class="mobile-offcanvas-logo">
                                <a href="/">
                                    <img src="/assets/img/logo-main.svg" alt="site logo" class="logo-sticky"
                                         style="width: 150px;">
                                </a>
                            </div>

                            <div class="close-menu" id="page-close-main-menu">
                                <i class="ti-close"></i>
                            </div>

                        </div>

                        <ul class="astriol-main-menu">
                            <li class="menu-item-depth-0">
                                <a href="/">Главная</a>
                            </li>
{{--                            <li class="has-submenu menu-item-depth-0">--}}
{{--                                <a href="##">О нас</a>--}}
{{--                                <ul class="sub-menu">--}}
{{--                                    <li><a href="##">Наша команда</a></li>--}}
{{--                                    <li><a href="##">Цены</a></li>--}}
{{--                                </ul>--}}
{{--                            </li>--}}
                            <li class="has-submenu menu-item-depth-0">
                                <a href="{{route('portfolio.all')}}">Портфолио</a>
                                <ul class="sub-menu">
                                    <li><a href="{{route('portfolio.all')}}">Все наши работы</a></li>
                                    @foreach($categories as $category)
                                        <li><a href="{{route('portfolio.index', $category->id)}}">{{$category->name}}</a></li>
                                    @endforeach
                                </ul>
                            </li>
{{--                            <li class="menu-item-depth-0">--}}
{{--                                <a href="##">Блог</a>--}}
{{--                            </li>--}}
{{--                            <li class="menu-item-depth-0">--}}
{{--                                <a href="##">Маркет</a>--}}
{{--                            </li>--}}
{{--                            <li class="menu-item-depth-0"><a href="##">Контакты</a></li>--}}
                        </ul>

                        <div class="nav-right">
                            <a href="{{route('pricing.index')}}" class="gp-btn color-eight btn-light btn-outline btn-round">
                                Цены
                            </a>
                        </div>


                    </div>
                    <!-- /.menu-wrapper -->

                    <div class="astriol-burger-menu" id="mobile-menu-open">
                        <span class="bar-one"></span>
                        <span class="bar-two"></span>
                        <span class="bar-three"></span>
                    </div>
                </nav>
                <!-- /.site-nav -->
            </div>
            <!-- /.header-inner -->
        </div>
        <!-- /.container-full -->
    </header>
    <!-- /.site-header -->

    @yield('content')

    <!--=========================-->
    <!--=        Footer         =-->
    <!--=========================-->
    <footer id="footer-agency" class="gp-site-footer">
        <div class="footer-inner">
            <div class="container">
                <div class="footer-widgets">
                    <div class="row">

                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="widget">
                                <div class="footer-logo">
                                    <a href="#"><img src="/assets/img/logo-main.svg" alt="" style="width: 200px"></a>
                                </div>

                                <h3 class="follow-title">Социальные сети</h3>
                                <ul class="footer-social-link style-two style-circle">
                                    <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                    <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                                    <li><a href="#"><i class="fab fa-whatsapp"></i></a></li>
                                    <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                                </ul>
                                <!-- /.footer-social -->
                            </div>
                            <!-- /.widget -->
                        </div>
                        <!-- /.col-xl-3 -->


                        <div class="col-lg-4 col-md-6 col-sm-6">
                            <div class="widget contact-widget">
                                <h3 class="widget-title">Контакты:</h3>
                                <br/>
                                <a href="tel:+77784344826" class="phone">
                                    +7 778 434 4826
                                </a>
                                <br/>
                                <a href="mailto:swebs.sh@gmail.com" class="phone">
                                    swebs.sh@gmail.com
                                </a>
                            </div>
                            <!-- /.widget -->
                        </div>
                        <!-- /.col-xl-3 -->

                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="widget">
                                <h3 class="widget-title">Полезные ссылки</h3>

                                <ul class="footer-menu">
                                    <li><a href="#">О нас</a></li>
                                    <li><a href="#">Цены</a></li>
                                    <li><a href="#">Портфолио</a></li>
                                    <li><a href="#">Контакты</a></li>
                                </ul>
                            </div>
                            <!-- /.widget -->
                        </div>
                        <!-- /.col-xl-3 -->

                        <div class="col-lg-2 col-md-6 col-sm-6">
                            <div class="widget">
                                <h3 class="widget-title">Рабочий процесс</h3>
                                <ul class="footer-menu">
                                    <li><a href="#">Этапы разработки</a></li>
                                    <li><a href="#">Шаблон договора</a></li>
                                </ul>
                            </div>
                            <!-- /.widget -->
                        </div>
                        <!-- /.col-xl-3 -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.footer-widgets -->

                <div class="site-info text-center">
                    <p class="copyright-text">© 2024 S-WEBS. Все права защищены</p>
                </div>
                <!-- /.site-info -->
            </div>
            <!-- /.footer-inner -->


        </div>
        <!-- /.container -->
    </footer>
    <!-- /#footer-medical.gp-site-footer -->


</div>
<!-- /#site -->

<!-- Dependency Scripts -->
<script src="/dependencies/jquery/jquery.min.js"></script>
<script src="/dependencies/bootstrap/js/bootstrap.min.js"></script>
<script src="/dependencies/swiper/js/swiper.min.js"></script>
<script src="/dependencies/isotope-layout/isotope.pkgd.min.js"></script>
<script src="/dependencies/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="/dependencies/magnific-popup/js/jquery.magnific-popup.min.js"></script>
<script src="/dependencies/jquery.appear/jquery.appear.js"></script>
<script src="/dependencies/wow/js/wow.min.js"></script>
<script src="/assets/js/TweenMax.min.js"></script>
<script src="/dependencies/countUp.js/countUp.min.js"></script>
<script src="/dependencies/bodymovin/lottie.min.js"></script>
<script src="/dependencies/jquery.parallax-scroll/js/jquery.parallax-scroll.js"></script>
<script src="/dependencies/wavify/wavify.js"></script>
<script src="/dependencies/jquery.marquee/js/jquery.marquee.js"></script>
<script src="/assets/js/jarallax.min.js"></script>
<script src="/dependencies/gmap3/js/gmap3.min.js"></script>
<script src="/dependencies/slick-carousel/js/slick.min.js"></script>
<script src="/assets/js/jquery.parallax.min.js"></script>
<script src='https://maps.googleapis.com/maps/api/js?key=AIzaSyDk2HrmqE4sWSei0XdKGbOMOHN3Mm2Bf-M'></script>


<!-- Site Scripts -->
<script src="/assets/js/header.js"></script>
<script src="/assets/js/app.js"></script>

</body>

</html>
