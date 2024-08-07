<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Team;

use MoonShine\Fields\Image;
use MoonShine\Fields\Json;
use MoonShine\Fields\Number;
use MoonShine\Fields\Position;
use MoonShine\Fields\Select;
use MoonShine\Fields\Slug;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Fields\TinyMce;
use MoonShine\Fields\Url;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;
use PhpParser\Node\Scalar\String_;

/**
 * @extends ModelResource<Team>
 */
class TeamResource extends ModelResource
{
    protected string $model = Team::class;

    protected string $title = 'Teams';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                Block::make([
                    Image::make('Image', 'image')
                        ->disk('public')
                        ->dir('site-media/team'),
                    Text::make('Имя', 'name'),
                    Text::make('Должность', 'position'),
                    TinyMce::make('Портфолио', 'portfolio')
                    ->hideOnIndex(),
                    Json::make('Социальные сети', 'social')
                    ->fields([
                        Position::make(),
                        Select::make('Тип', 'icon')
                        ->options([
                            'fab fa-facebook' => 'Facebook',
                            'fab fa-youtube' => 'Youtube',
                            'fab fa-instagram' => 'Instagram',
                            'fab fa-twitter' => 'Twitter',
                            'fab fa-linkedin' => 'LinkedIn',
                            'fab fa-youtube-play' => 'Youtube',
                            'fab fa-github' => 'Github',
                        ]),
                        Url::make('Ссылка', 'link')
                    ]),
                    Number::make('Order', 'order')
                        ->default(1),
                    Switcher::make('Активно', 'active')
                    ->default(1),
                    Slug::make('Slug', 'slug')
                        ->hideOnIndex()
                        ->from('name'),
                ]),
            ]),
        ];
    }

    /**
     * @param Team $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
