<?php
/*
Plugin Name: Google Sheets Sync
Description: Plugin for syncing data from Google Sheets to a custom post type with ACF fields.
Version: 1.0
Author: Hari Huynh
*/

class GoogleSheetsSyncPlugin
{
    const POST_TYPE = 'warranty_management';
    const SHEET_ID = '1V-wyvwu_SLUv9G2pRtJit3n521fhwOs9vZKXbvH_dFY';
    const SHEET_NAME = 'warranty!A:A';
    public string $sheet_id;
    public string $sheet_name;

    public function __construct()
    {
        $this->sheet_id = get_field('sheet_id', 'warranty_sheet_settings');
        $this->sheet_name = get_field('sheet_name', 'warranty_sheet_settings');
        add_action('save_post', [$this, 'append_custom_post_data_to_google_sheets']);
//// Kiểm tra nếu dữ liệu tồn tại trước khi sử dụng
        if (!$this->sheet_id && !$this->sheet_name) {
            error_log("Không có thông tin sheet_id và sheet_name.");
            wp_die("Không có thông tin sheet_id và sheet_name.");
        }
    }


    public function append_custom_post_data_to_google_sheets($post_id)
    {
        // Chỉ thực hiện nếu có dữ liệu POST và bài viết thuộc loại custom post type
        if (empty($_POST) || get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }

        // Lấy dữ liệu của các trường ACF trong bài viết
        $data = get_fields($post_id);

        // Kiểm tra xem có dữ liệu để gửi không
        if (empty($data)) {
            return;
        }

        // Chuyển dữ liệu thành mảng giá trị
        $values = array_values($data);

        // Đường dẫn đến tệp google_api_credentials.json
        $path = __DIR__ . '/google_api_credentials.json';

        // ID của bảng tính Google Sheets cần cập nhật
        $spreadsheet_id = $this->sheet_id;

        // Tên sheet trong bảng tính cần thêm dữ liệu
        $sheet_name = $this->sheet_name;

        // Đảm bảo bạn đã cài đặt thư viện Google API bằng Composer
        require_once('vendor/autoload.php');

        // Khởi tạo client của Google API
        $client = new Google_Client();
        $client->setAuthConfig($path);
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $service = new Google_Service_Sheets($client);

        // Tạo một mảng dữ liệu mới
        $newData = [$values];

        // Chuẩn bị dữ liệu để gửi lên Google Sheets
        $body = new Google_Service_Sheets_ValueRange(['values' => $newData]);
        $params = ['valueInputOption' => 'RAW'];
        // Thực hiện thêm dữ liệu mới vào sheet sử dụng phương thức append
        try {
            $result = $service->spreadsheets_values->append($spreadsheet_id, $sheet_name, $body, $params);
            // Xử lý kết quả ở đây nếu cần
        } catch (Google_Service_Exception $e) {
            // Xử lý lỗi nếu có
            error_log('Google Sheets API Error: ' . $e->getMessage());
        }
    }


}

