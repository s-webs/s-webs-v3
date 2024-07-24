@extends('layouts.master')
@section('content')

    @include('pages.home.components.banner')

    <!--========================-->
    <!--=         About        =-->
    <!--========================-->
    <section class="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-feature-image">
                        <div class="top-content wow fadeInLeft">
                            <h4 class="ab-top-subtitle">Who we Are</h4>
                            <h3 class="ab-subtitle">
                                Agency<br> Company
                            </h3>
                        </div>
                        <div class="image-wrapper wow fadeInRight">
                            <img src="/media/about/1.jpg" alt="astriol about">
                        </div>
                    </div>
                </div>
                <!-- /.col-lg-6 -->
                <div class="col-lg-6">
                    <div class="about-content-wrapper">
                        <h2 class="about-title wow fadeInUp">
                            Welcome to our Business <br> Digital Agency
                        </h2>
                        <p class="wow fadeInUp" data-wow-delay="0.3s">
                            Harry I nice one I don't want no agro such a fibber daft pukka some dodgy chav cheeky bugger
                            cheers A bit of how's your father say at public school, chip shop bleeder barney I no biggie
                            hanky panky cras absolutely.!
                        </p>

                        <ul class="icon-lists color-eight wow fadeInUp" data-wow-delay="0.5s">
                            <li><i class="ei ei-icon_check"></i>UI/UX design & app</li>
                            <li><i class="ei ei-icon_check"></i>Professional Assistance</li>
                            <li><i class="ei ei-icon_check"></i>Creative Design</li>
                        </ul>

                        <a href="about.html" class="gp-btn color-eight wow fadeInUp" data-wow-delay="0.7s">More About
                            Us</a>
                    </div>
                    <!-- /.about-content-wrapper -->
                </div>
                <!-- /.col-lg-6 -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->

        <!-- <div class="js-canvas-textbox" data-gradient-left="#1d3ede" data-gradient-right="#01e6f8">
    <canvas></canvas>
</div> -->
    </section>
    <!-- /.about -->

    <!--==========================-->
    <!--=         Feature        =-->
    <!--==========================-->
    <section class="feature-agency feature-padding">
        <div class="bg-shape">
            <img src="/media/background/3.png" class="wow fadeInLeft" alt="astriol shape bg">
        </div>
        <!-- /.bg-shape -->

        <div class="container">
            <div class="section-heading style-two">
                <h3 class="subtitle wow fadeInUp">Services</h3>
                <h2 class="section-title wow fadeInUp" data-wow-delay="0.3s">Best Solutions for You</h2>
            </div>

            <div class="row">
                <div class="col-lg-4 col-sm-6 col-md-6">
                    <div class="icon-box style-three wow fadeInRight" data-wow-delay="0.5s">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                 width="127px" height="94px">
                                <path fill-rule="evenodd" opacity="0.078" fill="rgb(233, 167, 26)"
                                      d="M70.603,37.711 C101.750,37.711 127.000,6.625 127.000,37.711 C127.000,68.798 101.750,93.999 70.603,93.999 C39.455,93.999 9.153,63.668 2.926,33.209 C-12.866,-44.033 39.455,37.711 70.603,37.711 Z"/>
                            </svg>
                            <img src="/media/feature/1.png" alt="service">
                        </div>

                        <div class="box-content">
                            <h3 class="box-title">
                                <a href="service.html">Highly Customizable</a>
                            </h3>

                            <p>
                                Tosser car boot ummm I'm telling hanky panky blower brown bread horse play boot
                                starkers.!
                            </p>
                            <a href="#" class="gp-btn color-eight btn-outline">Learn More</a>
                        </div>
                    </div>

                    <!-- /.icon-box -->
                </div>
                <!-- /.col-lg-4 col-sm-6 col-md-6 -->

                <div class="col-lg-4 col-sm-6 col-md-6">
                    <div class="icon-box style-three wow fadeInRight" data-wow-delay="0.7s">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                 width="110px" height="101px">
                                <path fill-rule="evenodd" opacity="0.078" fill="rgb(77, 90, 254)"
                                      d="M55.002,43.519 C77.774,66.637 133.891,21.810 98.647,76.302 C84.775,97.749 -27.184,120.078 7.044,69.959 C25.345,43.163 -9.947,35.696 26.692,6.684 C52.457,-13.716 32.230,20.400 55.002,43.519 Z"/>
                            </svg>

                            <img src="/media/feature/2.png" alt="service">
                        </div>

                        <div class="box-content">
                            <h3 class="box-title">
                                <a href="service.html">UI/UX Design</a>
                            </h3>

                            <p>
                                Tosser car boot ummm I'm telling hanky panky blower brown bread horse play boot
                                starkers.!
                            </p>
                            <a href="#" class="gp-btn color-eight btn-outline">Learn More</a>
                        </div>
                    </div>

                    <!-- /.icon-box -->
                </div>
                <!-- /.col-lg-4 col-sm-6 col-md-6 -->

                <div class="col-lg-4 col-sm-6 col-md-6">
                    <div class="icon-box style-three wow fadeInRight" data-wow-delay="0.9s">
                        <div class="icon-container">

                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                 width="153px" height="91px">
                                <path fill-rule="evenodd" opacity="0.078" fill="rgb(252, 98, 72)"
                                      d="M84.088,71.208 C105.396,49.760 192.817,115.939 131.565,32.447 C117.507,13.285 92.151,2.630 70.842,24.079 C49.534,45.527 -25.059,-43.676 8.580,32.102 C45.328,114.883 62.779,92.657 84.088,71.208 Z"/>
                            </svg>

                            <img src="/media/feature/3.png" alt="service">
                        </div>

                        <div class="box-content">
                            <h3 class="box-title">
                                <a href="service.html">Creative Design</a>
                            </h3>

                            <p>
                                Tosser car boot ummm I'm telling hanky panky blower brown bread horse play boot
                                starkers.!
                            </p>

                            <a href="#" class="gp-btn color-eight btn-outline">Learn More</a>
                        </div>
                    </div>

                    <!-- /.icon-box -->
                </div>
                <!-- /.col-lg-4 col-sm-6 col-md-6 -->
            </div>
            <!-- /.row -->


        </div>
        <!-- /.container -->
    </section>
    <!-- /.feature-agency -->

    <!--==============================-->
    <!--=         Intro Video        =-->
    <!--==============================-->
    <section class="intro-video">
        <div class="container">
            <div class="intro-video-wrapper">
                <div class="row align-items-center">
                    <div class="col-xl-7 col-lg-6 mw-670">
                        <div class="video-content-wrapper">

                            <h3 class="subtitle wow fadeInUp">EASY DEPLOYMENT</h3>
                            <h2 class="title wow fadeInUp" data-wow-delay="0.3s">Deploy your Site with Simple
                                Commands</h2>

                            <p class="wow fadeInUp" data-wow-delay="0.5s">
                                Cack me old mucker wind up spiffing Elizabeth codswallop are you taking the piss, cockup
                                haggle only a quid knackered blatant mufty arse, hanky panky ruddy some dodgy chav it's
                                all gone to pot say.
                            </p>

                            <a href="about.html" class="gp-btn color-eight wow fadeInUp" data-wow-delay="0.7s">
                                Learn More
                            </a>
                        </div>
                        <!-- /.video-content-wrapper -->

                    </div>
                    <!-- /.col-xl-7 col-lg-6 -->

                    <div class="popup-video-warapper">
                        <div class="intro-video-inner wow fadeInRight">
                            <img src="/media/about/2.jpg" alt="intro video">
                            <a href="https://www.youtube.com/watch?v=9No-FiEInLA" class="popup-video wow pixFadeUp"
                               data-wow-delay="0.6s">
                                <i class="fa fa-play"></i>
                            </a>
                        </div>
                        <!-- /.intro-video -->
                    </div>
                    <!-- /.popup-video-arapper -->

                </div>
                <!-- /.row -->
            </div>
            <!-- /.intro-video-innter -->
        </div>
        <!-- /.container -->
    </section>
    <!-- /.intro-video -->

    @include('pages.home.components.portfolio')

    <!--===========================-->
    <!--=         Pricing         =-->
    <!--===========================-->
    <section class="pricings-two">
        <div class="container">
            <div class="section-heading style-two">
                <h3 class="subtitle">Pricing Plan</h3>
                <h2 class="section-title">Get in Reasonable Price</h2>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="pricing-table style-two color-one wow fadeInLeft" data-wow-delay="0.3s">
                        <div class="price-header">
                            <div class="price-period">
                                <h2 class="price">$0.00</h2>
                            </div>
                            <!-- /.price-period -->
                            <h3 class="price-title">FREE</h3>
                            <span class="period">Single User</span>
                        </div>
                        <!-- /.price-header -->

                        <ul class="price-feature">
                            <li><i class="ei ei-icon_check"></i> 50 MB Disk Space</li>
                            <li><i class="ei ei-icon_check"></i> 2 Subdo Mains</li>
                            <li><i class="ei ei-icon_close"></i> 6 Email Accounts</li>
                            <li><i class="ei ei-icon_close"></i> Analytics</li>
                            <li><i class="ei ei-icon_close"></i> Phone & Mail Support</li>
                        </ul>
                        <a href="contact.html" class="gp-btn color-eight btn-outline ">Get Started</a>
                    </div>
                    <!-- /.pricing-table -->
                </div>
                <!-- /.col-lg-4 col-md-6 col-sm-6 -->

                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="pricing-table style-two feature-item wow fadeInLeft" data-wow-delay="0.5s">
                        <div class="price-header">
                            <div class="price-period">
                                <h2 class="price">$19.00</h2>
                            </div>
                            <!-- /.price-period -->
                            <h3 class="price-title">Reguler</h3>
                            <span class="period">Single User, Billed Monthly</span>
                        </div>
                        <!-- /.price-header -->

                        <ul class="price-feature">
                            <li><i class="ei ei-icon_check"></i> 100 MB Disk Space</li>
                            <li><i class="ei ei-icon_check"></i> 3 Subdo Mains</li>
                            <li><i class="ei ei-icon_check"></i> 10 Email Accounts</li>
                            <li><i class="ei ei-icon_check"></i> Analytics</li>
                            <li><i class="ei ei-icon_close"></i> Phone & Mail Support</li>
                        </ul>
                        <a href="contact.html" class="gp-btn color-eight">Get Started</a>

                        <span class="trial">Get your 7 day free trial</span>
                    </div>
                    <!-- /.pricing-table -->
                </div>
                <!-- /.col-lg-4 col-md-6 col-sm-6 -->

                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="pricing-table style-two color-two wow fadeInLeft" data-wow-delay="0.7s">
                        <div class="price-header">
                            <div class="price-period">
                                <h2 class="price">$49.00</h2>
                            </div>
                            <!-- /.price-period -->
                            <h3 class="price-title">Business</h3>
                            <span class="period">Single User, Billed Monthly</span>
                        </div>
                        <!-- /.price-header -->

                        <ul class="price-feature">
                            <li><i class="ei ei-icon_check"></i> 200 MB Disk Space</li>
                            <li><i class="ei ei-icon_check"></i> 6 Subdo Mains</li>
                            <li><i class="ei ei-icon_check"></i> 20 Email Accounts</li>
                            <li><i class="ei ei-icon_check"></i> Analytics</li>
                            <li><i class="ei ei-icon_check"></i> Phone & Mail Support</li>
                        </ul>
                        <a href="contact.html" class="gp-btn color-eight btn-outline ">Get Started</a>
                        <span class="trial">Get your 7 day free trial</span>
                    </div>
                    <!-- /.pricing-table -->
                </div>
                <!-- /.col-lg-4 col-md-6 col-sm-6 -->

            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->
    </section>
    <!-- /.pricings-two -->

    @include('pages.home.components.testimonial')

    @include('pages.home.components.blog')

    @include('pages.home.components.callToAction')

@endsection
