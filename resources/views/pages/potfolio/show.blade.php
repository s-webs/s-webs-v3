@extends('layouts.master')
@section('content')
    <section class="page-banner portfolio-banner-bg" data-bg-image="/{{$project->image_main}}">
        <div class="container page-banner-pr">
            <div class="page-title-wrapper">
                <h1 class="page-title">
                    {{$project->name}}
                </h1>
            </div>
        </div>
    </section>

    <section class="portfolio-single">
        <div class="container">
            <div class="portfolio__content">
                <div class="portfolio__content-top">
                    <div class="portfolio__content-des">
                        <h3 class="title">Описание проекта</h3>
                        <p>
                            {{$project->description}}
                        </p>
                    </div>
                    <!-- /.portfolio__content-des -->

                    <div class="portfolio__content-info">
                        <ul class="portfolio__info">
                            <li>
                                <h5 class="info-title">Год разработки</h5>
                                <span class="info">{{$project->year}}</span>
                            </li>
                            <li>
                                <h5 class="info-title">Клиент</h5>
                                @if($project->link)
                                    <a href="{{$project->link}}" class="info" target="_blank">{{$project->client}}</a>
                                @else
                                    <span class="info">{{$project->client}}</span>
                                @endif
                            </li>
                            @if($project->link)
                                <li>
                                    <h5 class="info-title">Ссылка</h5>
                                    <a href="{{$project->link}}" class="info" target="_blank">Посмотреть</a>
                                </li>
                            @endif
                        </ul>

                        <div class="portfolio__category-inner">
                            <h3 class="cat-title">Тип сайта</h3>
                            <a href="{{route('portfolio.index', $project->category->id)}}">{{$project->category->name}}</a>
                        </div>
                    </div>
                    <!-- /.portfolio__content-info -->
                </div>
                <!-- /.portfolio__content-top -->

                <div class="portfolio--single-gallery">
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="gallery--image">
                                @if($project->image_770x500_1)
                                    <img src="/{{$project->image_770x500_1}}" alt="{{$project->name}}">
                                @endif
                                @if($project->image_770x500_2)
                                    <img src="/{{$project->image_770x500_2}}" alt="{{$project->name}}">
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="gallery--image">
                                @if($project->image_370x600)
                                    <img src="/{{$project->image_370x600}}" alt="{{$project->name}}">
                                @endif
                                @if($project->image_370x400)
                                    <img src="/{{$project->image_370x400}}" alt="{{$project->name}}">
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.portfolio--single-gallery -->
                <ul class="portfolio-nav">
                    <li class="prev">
                        @if($previousProject)
                            <i class="ei ei-arrow_left"></i>
                            <a href="{{$previousProject->slug}}">Предыдудущий</a>
                        @endif
                    </li>

                    <li class="next">
                        @if($nextProject)
                            <a href="{{$nextProject->slug}}">Следующий</a>
                            <i class="ei ei-arrow_right"></i>
                        @endif
                    </li>
                </ul>
            </div>
            <!-- /.portfolio__contents -->

        </div>
        <!-- /.container -->

        <div class="portfolio--releted-wrapper">
            <div class="container">

                <div class="related-portfolio">
                    <div class="section-heading text-center">
                        <h3 class="subtitle">Портфолио</h3>
                        <h2 class="section-title">Последние проекты</h2>
                    </div>
                    <!-- /.section-title text-center -->


                    <div class="swiper-container" id="related-portfolio" data-speed="700" data-autoplay="5000"
                         data-perpage="3" data-space="30"
                         data-breakpoints='{"1200": {"slidesPerView": 4, "spaceBetween": 50}, "768": {"slidesPerView": 3, "spaceBetween": 30}, "480": {"slidesPerView": 2, "spaceBetween": 30}, "320": {"slidesPerView": 1, "spaceBetween": 30}}'>
                        <div class="swiper-wrapper">
                            @foreach($lastProjects as $project)
                                <div class="swiper-slide">
                                    <div class="portfolio-item">
                                        <div class="feature-image">
                                            <a href="{{route('portfolio.show', $project->slug)}}">
                                                <img src="/{{$project->image_preview}}" alt="{{$project->name}}">
                                            </a>
                                        </div>

                                        <div class="port-info">
                                            <h3>
                                                <a href="{{route('portfolio.show', $project->slug)}}">
                                                    {{$project->name}}
                                                </a>
                                            </h3>

                                            <span><a
                                                    href="{{route('portfolio.index', $project->category->id)}}">{{$project->category->name}}</a></span>
                                        </div>
                                    </div>
                                    <!-- /.portfolio-item -->
                                </div>
                            @endforeach
                        </div>
                        <!-- /.swiper-wrapper -->
                        <div class="swiper-pagination"></div>
                    </div>
                    <!-- /.swiper-container -->
                </div>
                <!-- /.related-portfolio -->
            </div>
            <!-- /.container -->
        </div>
        <!-- /.portfolio--releted-wrapper -->
    </section>
    <!-- /.portfolio-single -->

    @include('pages.home.components.action')
@endsection
