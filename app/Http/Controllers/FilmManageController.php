<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class FilmManageController extends Controller
{
    //

    public function listmovie()
    {
        //lấy toàn bộ movier
        $movieGenres= DB::table('movies')
        ->leftJoin('movie_genres', 'movie_genres.movie_id','=','movies.id')
        ->leftJoin('genres', 'movie_genres.genre_id','=', 'genres.id')
        ->select('movies.*',
        DB::raw('GROUP_CONCAT(genres.name SEPARATOR ", ") as genres_name ')
        )->groupBy('movies.id')
        ->paginate(10)
        ;
        return view('admin.film_management.film',compact('movieGenres'));
    }


    //form thêm film
    public function formadd(Request $request){
        //đổ thể loại cho view
        $genres= Genre::all();
        return view('admin.film_management.addfilm',compact('genres'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Thông tin cơ bản
            'title' => 'required|string|max:255',
            'original_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            // Phát hành
            'duration_minutes' => 'required|integer|min:1|max:500',
            'release_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:release_date',
            'status' => 'required|in:COMING_SOON,NOW_SHOWING,ENDED,HIDDEN',

            // Nội dung
            'country' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string|max:100',
            'director' => 'nullable|string|max:255',
            'age_rating' => 'required|in:P,K,T13,T16,T18',
            'cast' => 'nullable|string',

            // Thể loại (checkbox array)
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',

            // Media
            'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'trailer_url' => 'nullable|url',
        ], [
            // Custom message (tuỳ chọn)
            'title.required' => 'Tên phim không được để trống',
            'duration_minutes.required' => 'Vui lòng nhập thời lượng phim',
            'release_date.required' => 'Ngày khởi chiếu là bắt buộc',
            'status.in' => 'Trạng thái không hợp lệ',
            'age_rating.in' => 'Độ tuổi không hợp lệ',
        ]);

        //kiểm tra xem nếu có hệ thống có cập nhật poster
        if($request->hasFile('poster')){
            //kiểm tra hasFile() xem form đẩy lên có ô input file tên poster không
            $poster = $request->file('poster')?->store('poster_film','public');
        }

        //kiểm tra xem nếu hệ thống có đẩy banner lên ko
        if($request->hasFile('banner')){
            $banner= $request->file('banner')?->store('banner_film','public');
        }


        $movie = Movie::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'original_title' => $request->original_title,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'release_date' => $request->release_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'country' => $request->country,
            'language' => $request->language,
            'subtitle' => $request->subtitle,
            'director' => $request->director,
            'age_rating' => $request->age_rating,
            'cast' => $request->cast,
            'poster_url' => $poster ,
            'banner_url' => $banner ,
            'trailer_url' => $request->trailer_url ?? null,
        ]);
        $movie->genres()->sync($request->input('genres', []));


        return redirect()->route('admin.film')->with('success', 'Phim đã được thêm thành công!');
    }
}
