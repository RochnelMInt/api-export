<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    private $descendants = [];

    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function actualites(){

        return $this->hasMany(Actualite::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function parent()
    {
        return $this->belongsToMany(Category::class, 'parent_category', 'category_id', 'child_category_id');
    }

    public function getTypeNumberAttribute()
    {
        return $this->attributes['type'] ? array_search($this->attributes['type'], $this->getTypeOptions()) + 1 : null;
    }
    
    public function getTypeOptions()
    {
        return ['ARTICLE', 'ACTUALITE'];
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class);
    }

    public function getParent(){
    
        if($this->parent->count() > 0 ){
            return $this->parent[0]->id;
        }else{
            return 0;
        }
    }
    
    public function findDescendants(Category $category){
        $this->descendants[] = $category;
    
        if($category->parent->count() > 0 ){
            foreach($category->parent as $child){
                $this->out->writeln("From Laravel API : " . $child->name);
                $this->findDescendants($child);
            }
        }
    }
    
    public function getDescendants(Category $category){
        $this->findDescendants($category);
        return $this->descendants;
    }

    public function getFeatures()
    {
        $childCategories = $this->belongsToMany(Category::class, 'parent_category', 'category_id', 'child_category_id');

        return $childCategories;
    }

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function selectedCategories(){

        return $this->hasMany(SelectedCategory::class);
    }
}
