@extends('layouts.master')
@section('content')
    <section class="page-banner banner-bg" data-bg-image="media/portfolio/portfolio_1920x400.png">
        <div class="page-title-wrapper text-center">
            <h1 class="page-title">Наши работы</h1>
            <ul class="breadcrumbs">
                @if(isset($category))
                    <li>{{$category->name}}</li>
                @else
                    <li>Все проекты</li>
                @endif
            </ul>
        </div>
    </section>

    <section class="portfolios">
        <div class="container">
            <div class="portfolio-inner">
                <div class="section-heading style-three text-left">
                    <h2 class="section-title">
                        <span>S-WEBS</span><br>
                        Закажите сайт своей мечты
                    </h2>
                </div>
                <!-- /.section-heading -->
                <div class="astriol__isotope wow fadeIn" data-wow-delay="0.3s">
                    <div class="astriol__portfolio-items portfolio-one column-2 port-gutters">
                        <div class="grid-sizer"></div>
                        @foreach($projects as $project)
                            <div class="astriol__portfolio grid-item">
                                <div class="astriol__portfolio-image">
                                    <img src="/{{$project->image_preview}}" alt="portfolio thumb"/>
                                    <div class="portfolio-info">
                                        <h3 class="portfolio-title">
                                            <a href="{{route('portfolio.show', $project->slug)}}">{{$project->name}}</a>
                                        </h3>
                                        <span>{{$project->category->name}}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- /.portfolio-inner -->
        </div>
        <!-- /.container -->
    </section>
    <!-- /.portfolios -->
    @include('pages.home.components.action')
@endsection
