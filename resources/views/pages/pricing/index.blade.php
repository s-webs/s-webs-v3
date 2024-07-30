@extends('layouts.master')

@section('content')
    <section class="page-banner">
        <div class="page-title-wrapper text-center">
            <h1 class="page-title">Ознакомьтесь с нашими ценами</h1>
        </div>
        <!-- /.page-title-wrapper -->

        <ul class="banner-pertical">
            <li><img src="/media/banner/header/crose.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/box.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot_sm.png" data-parallax='{"y": 100}' alt="astriol pertical"></li>
            <li><img src="/media/banner/header/line.png" data-parallax='{"y": 50, "x": 100}' alt="astriol pertical">
            </li>
            <li data-parallax='{"y": -100}'></li>
            <li></li>
        </ul>
        <!-- /.banner-pertical -->

    </section>
    <!-- /.page-banner -->

    <section class="pricings-two-page">
        <div class="container">
            <div class="section-heading style-two color-theme">
                <h3 class="subtitle">прайс лист</h3>
                <h2 class="section-title">Наши расценки</h2>
            </div>

            <div class="row">

                @foreach($prices as $price)
                    @if($price->is_popular)
                        <div class="col-lg-4 col-md-6 col-sm-6">
                            <div class="pricing-table style-two color-theme feature-item wow fadeInLeft"
                                 data-wow-delay="0.5s">
                                <div class="price-header">
                                    <div class="price-period">
                                        <h2 class="price">от {{$price->cost}} ₸</h2>
                                    </div>
                                    <h3 class="price-title">{{$price->name}}</h3>
                                </div>

                                <ul class="price-feature" style="margin-bottom: 0;">
                                    @foreach($price->options as $option)
                                        <li><i class="ei ei-icon_check"></i> {{$option}}</li>
                                    @endforeach
                                </ul>
                                @if($price->slug)
                                    <a href="{{route('pricing.show', $price->slug)}}" class="gp-btn btn-outline"
                                       style="margin-top: 45px;">Подробнее</a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="col-lg-4 col-md-6 col-sm-6">
                            <div class="pricing-table style-two color-theme color-two wow fadeInLeft"
                                 data-wow-delay="0.7s">
                                <div class="price-header">
                                    <div class="price-period">
                                        <h2 class="price">от {{$price->cost}} ₸</h2>
                                    </div>
                                    <h3 class="price-title">{{$price->name}}</h3>
                                </div>
                                <ul class="price-feature" style="margin-bottom: 0;">
                                    @foreach($price->options as $option)
                                        <li><i class="ei ei-icon_check"></i> {{$option}}</li>
                                    @endforeach
                                </ul>
                                @if($price->slug)
                                    <a href="{{route('pricing.show', $price->slug)}}" class="gp-btn btn-outline"
                                       style="margin-top: 45px;">Подробнее</a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
    @include('pages.home.components.action')
@endsection
