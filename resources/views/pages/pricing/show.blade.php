@extends('layouts.master')
@section('content')
    <section class="page-banner">
        <div class="page-title-wrapper text-center">
            <h1 class="page-title">{{ $item->seo_title ?? $item->name ?? 'S-WEBS' }}</h1>
        </div>
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
    </section>

    <div class="blog-list-page">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="astriol__blog-single">
                        {!! $item->description !!}
                    </div>
                </div>

                <div class="col-md-4 sidebar-container sidebar-widget-area">
                    <aside class="sidebar">
                        <div id="gp-posts-widget-2" class="widget gp-posts-widget">
                            <h2 class="widget-title">Интересное</h2>
                            <div class="gp-posts-widget-wrapper">
                                @foreach($prices as $price)
                                    @if($price->id != $item->id)
                                        <div class="post-item">
                                            <div class="post-widget-info">
                                                <h5 class="post-widget-title">
                                                    <a href="{{route('pricing.show', $price->slug)}}">{{$price->seo_title ?? $price->name}}</a>
                                                </h5>
                                                <ul class="post-meta">
                                                    <li style="font-size: 0.8rem;">{{$price->getFormattedDateAttribute()}}</li>
                                                </ul>

                                            </div>
                                        </div>
                                    @else
                                        <div class="post-item">
                                            <div class="post-widget-info">
                                                <h5 class="post-widget-title">
                                                    Появятся в скором времени!
                                                </h5>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div id="categories" class="widget widget_categories">
                            <h2 class="widget-title">Наши работы</h2>
                            <ul>
                                @foreach($categories as $category)
                                    <li>
                                        <a href="{{route('portfolio.index', $category->id)}}">{{$category->name}} <span
                                                class="count">{{$category->projects->count()}}</span></a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
    @include('pages.home.components.action')
@endsection
