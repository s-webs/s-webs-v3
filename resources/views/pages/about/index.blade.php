@extends('layouts.master')
@section('content')
    <section class="page-banner">
        <div class="page-title-wrapper text-center">
            <h1 class="page-title">О нас</h1>
        </div>

        <ul class="banner-pertical">
            <li><img src="/media/banner/header/crose.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/box.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot.png" alt="astriol pertical"></li>
            <li><img src="/media/banner/header/dot_sm.png" data-parallax='{"y": 100}' alt="astriol pertical"></li>
            <li><img src="/media/banner/header/line.png" data-parallax='{"y": 50, "x": 100}' alt="astriol pertical"></li>
            <li data-parallax='{"y": -100}'></li>
            <li></li>
        </ul>
    </section>

    <section class="teams">
        <div class="container">
            <div class="section-heading style-two">
                <h2 class="section-title border-none">
                    Наша команда
                </h2>
            </div>
            <div class="row">
                @foreach($teams as $team)
                    <div class="col-md-4 col-sm-6">
                        <div class="team-member">
                            <div class="team-avatar">
                                <img src="/{{$team->image}}" alt="{{$team->name}}">

                                <ul class="member-social">
                                    @foreach($team->social as $item)
                                        <li><a href="{{$item['link']}}" target="_blank"><i
                                                    class="{{$item['icon']}}"></i></a></li>
                                    @endforeach
                                    @if($team->portfolio)
                                        <li>
                                            <a href="{{route('about.show', $team->slug)}}"><i class="fa fa-link"></i></a>
                                        </li>
                                    @endif
                                </ul>
                            </div>

                            <div class="member-info">
                                <h3 class="name">{{$team->name}}</h3>
                                <span class="designation">{{$team->position}}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->
    </section>
    <!-- /.teams -->
@endsection
