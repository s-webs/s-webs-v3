@extends('layouts.master')
@section('content')
    <section class="page-banner">
        <div class="page-title-wrapper text-center">
            <h1 class="page-title">{{ $team->seo_h1 ?: $team->name.' | Портфолио' }}</h1>
        </div>

        <ul class="banner-pertical">
            <li><img src="/media/banner/header/crose.webp" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/box.webp" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot.webp" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot_sm.webp" data-parallax='{"y": 100}' alt="astriol pertical"></li>
            <li><img src="/media/banner/header/line.webp" data-parallax='{"y": 50, "x": 100}' alt="astriol pertical">
            </li>
            <li data-parallax='{"y": -100}'></li>
            <li></li>
        </ul>
    </section>
    <section class="portfolio-single">
        <div class="container">
            <div class="portfolio__content" style="padding-bottom: 60px;">
                <div class="portfolio__content-top" style="padding-bottom: 0;">
                    <div class="portfolio__content" style="padding-bottom: 0;">
                        {!! $team->portfolio !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
    @include('pages.home.components.action')
@endsection
