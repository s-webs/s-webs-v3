<section class="portfolio-sliders">
    <div class="container">
        <div class="section-heading text-left style-two">
            <h3 class="subtitle wow fadeInUp">Портфолио</h3>
            <h2 class="section-title wow fadeInUp" data-wow-delay="0.3s">Последние работы</h2>

            <div class="social-slider-nav">
                <div id="stesti-prev" class="slider-prev" tabindex="0" role="button" aria-label="Previous slide">
                    <i class="ei ei-arrow_carrot-left"></i>
                </div>
                <div id="stesti-next" class="slider-next" tabindex="0" role="button" aria-label="Next slide">
                    <i class="ei ei-arrow_carrot-right"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container -->

    <div class="portfolio-slider-wrapper">
        <div class="swiper-container wow fadeInUp" data-wow-delay="0.5s" id="portfolio-slider" data-space="40" data-autoplay="7000" data-breakpoints='{"1200": {"slidesPerView": 4, "spaceBetween": 50}, "768": {"slidesPerView": 3, "spaceBetween": 30}, "480": {"slidesPerView": 2, "spaceBetween": 30}, "320": {"slidesPerView": 1, "spaceBetween": 30}}'>
            <div class="swiper-wrapper">
                @foreach($projects as $project)
                    <div class="swiper-slide">
                        <div class="portfolio-item-slide">
                            <div class="portfolio-image">
                                <a href="/{{$project->image_preview}}" class="popup-image">
                                    <img src="/{{$project->image_preview}}" alt="portfolio">
                                </a>
                            </div>
                            <!-- /.portfolio-image -->

                            <div class="portfolio-content">
                                <h3 class="title"><a href="{{route('portfolio.show', $project->slug)}}">{{$project->name}}</a></h3>
                                <span class="port-cat">{{$project->category->name}}</span>
                            </div>
                        </div>
                        <!-- /.portfolio-item-slide -->
                    </div>
                @endforeach
            </div>
            <!-- /.swiper-wrapper -->
        </div>
        <!-- /.swiper-container -->
    </div>
    <!-- /.slider-wrapper -->


    <div class="white-shape"></div>
</section>
