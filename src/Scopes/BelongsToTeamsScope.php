<?php

namespace VentureDrake\LaravelCrm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BelongsToTeamsScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['AllTeams'];

    /**
     * Bypass the slow Schema::hasColumn() method for these tables.
     *
     * @var string[]
     */
    protected $include = [
        'settings'
    ];

    public function apply(Builder $builder, Model $model)
    {
        if (config('laravel-crm.teams') && auth()->hasUser() && auth()->user()->currentTeam) {
            if(! config('nova.path') || (config('nova.path') && ! Str::startsWith(request()->getRequestUri(), config('nova.path')))) {
                $this->extend($builder);
                
                if (in_array($model->getTable(), $this->include) || Schema::hasColumn($model->getTable(), 'global')) {
                    $builder->where(function ($query) use ($model) {
                        $query->orWhere($model->getTable().'.team_id', auth()->user()->currentTeam->id)
                            ->orWhere($model->getTable().'.global', 1);
                    });
                } else {
                    $builder->where($model->getTable().'.team_id', auth()->user()->currentTeam->id);
                }
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addAllTeams(Builder $builder)
    {
        $builder->macro('allTeams', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
