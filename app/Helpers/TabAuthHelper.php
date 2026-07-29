<?php
namespace App\Helpers;

use App\Models\TabToken;
use Illuminate\Support\Facades\Request;
class TabAuthHelper {
    /** lấy user dựa trên token trên url hiện tại */

    // hàm mà có static là hàm ko cần phải khỏi tạo lại đối tượng (new) mà có thể gọi trực tiếp hàm thông qua class

    public static function currentUser()
    {
        $token = Request::query('tab_token');
        //nếu ko có token trên url thì là null
        if(!$token){
            return null;

        }
        //nếu tồn tại thì truy vấn
        $TabToken = TabToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        // nếu có token mà kiểm tra ko có thì cx cho null
        if(!$TabToken){
            return null;}
            //lấy ra thông tin tài khoản token dựa theo mối quan hệ trong model
            $user = $TabToken->user;
            // chỉ trả về tài khoản còn hoạt động
            if(!$user || $user->status !== 'ACTIVE'){
                return null;
            }

            return $user;
        }

        // từ đây lấy token để gán cho các url khác
        // dùng : ? string để đảm bảo hàm trả về string   vd:abcd1234 hoặc null nếu ko có token
        public static function gettoken(): ? string {
            return Request::query('tab_token');

        }
        //add token cho url (cách dùng cho các url form dạng   đường dẫn ví dụ 'admin/dasbo?rd'  thì sẽ thành 'admin/dashboard?tab_token=abcd1234')
        public static function addToken (string $url): string {
            $token = static::gettoken();

            //nếu ko có token thì trả về token gốc
            if(!$token){
                return $url;
            }

            if (str_contains($url, 'tab_token=')) {
                return $url;
            }

            // dùng str_contains(phần mẫu cần kiểm tra, cái cần kiểm tra)
            $separator = str_contains($url, '?') ? '&' : '?';
            return $url . $separator . 'tab_token=' . $token;
        }

        //cách 2 là làm theo kiểu route () name của route 
        // vd: route('admin.dashboard') thì sẽ thành route('admin.dashboard', ['tab_token' => 'abcd1234'])
        public static function  route(string $name, $params = [], bool $absolute = false): string {
        //$name = tên route bạn đã đặt trong web.php (ví dụ 'admin.store.film')
        // $params = tham số nếu route có (ví dụ ['id' => 5] hoặc 5)
        // false = chỉ lấy đường dẫn, không lấy domain

        //Giả sử trong web.php bạn có:Route::post('/admin/filmstore', ...)->name('admin.store.film');

          /* Thì: route('admin.store.film', [], false)
           sẽ trả về:
          /admin/filmstore */ 
        $url = route($name, $params, $absolute);
        //gọi hàm addtoken để ghép
        return static::addToken($url);
        }


    }
?>
