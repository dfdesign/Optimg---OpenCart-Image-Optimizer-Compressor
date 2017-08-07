<?php
class ControllerModuleDfdesignOptimg extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('module/dfdesign_optimg');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module');

        $data['heading_title'] = $this->language->get('heading_title'); 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
        );

        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('module/dfdesign_optimg', 'token=' . $this->session->data['token'], 'SSL')
            );
        }else{
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('module/dfdesign_optimg', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
            );
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        define('WEBSERVICE', 'http://api.resmush.it/ws.php?img=');

        getAllFiles();

        getAlreadyOptimisedFiles();

        $data['number_of_optimised'] = $optimised;
        $data['number_of_not_optimised'] = count($all_files) - count($optimised_files);
        $not_optimised_files_number = count($all_files) - count($optimised_files);
        if(count($all_files) - count($optimised_files) <= 0){
            $not_optimised_files_number == 0;
        }

        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if(isset($_POST['action']) && !empty($_POST['action'])) {


                getAllFiles();

                getAlreadyOptimisedFiles();

                foreach ($all_files as $key => $value) {
                    if (in_array($all_files[$key]['name'], $optimised_files)) {
                        unset($all_files[$key]);
                    }
                }
                $all_files_new = array_values($all_files);

                //number files for optimisation
                $filesForOptimisation = 1;
                $all_files_in_folder = $all_files_in_folder - $optimised;
                if ( $all_files_in_folder < $filesForOptimisation ) {
                    $filesForOptimisation = $all_files_in_folder;
                }

                if($all_files_in_folder>0)
                { 
                    $img_url = explode('public_html', $all_files_new[0]['name'], 2);
                    $old_size = $all_files_new[0]['size'];
                    $data['img_urla'] = 'http://'.$_SERVER['SERVER_NAME'] . $img_url[1];
                    $image_full_url = $data['img_urla'];
                    $image_full_url = str_replace(' ','%20',$image_full_url);

                    $prefix_url = explode("public_html",DIR_IMAGE,2);
                    $rel_path_new_url = $prefix_url[0]."public_html".$img_url[1];

                    
                    $s = $image_full_url;
                    $o = json_decode(file_get_contents(WEBSERVICE . $s.'&qlty=90'));

                    if(isset($o->error)){
                        echo json_encode('ERROR'.$o->error . $image_full_url );
                        die('Error. Please, check help page.');
                    }

                    if(copy($o->dest,$rel_path_new_url)){
                        // new size
                        $new_img = get_headers($o->dest, 1);$new_size = $new_img["Content-Length"];

                        // $opti_size = $old_size - $new_size;
                        $opti_size = number_format(($new_size / $old_size ) * 100,2);$opti_size = 100 - $opti_size;
                        $cached_file = DIR_IMAGE . 'cache/optimised.dat';
                        $lines = '';
                        $record_url = $rel_path_new_url. '\r\n';
                        file_put_contents($cached_file, $record_url, FILE_APPEND);

                        $json_arr[0]['url']        = $rel_path_new_url;
                        $json_arr[0]['percentage'] = $opti_size;

                        $data['optimised_images'] = self::getOptimisedImages();

                        // prep json encode
                        $json_array[0]['url']               = $rel_path_new_url;
                        $json_array[0]['old_size']          = '<td>'.$old_size.'</td>';
                        $json_array[0]['new_size']          = '<td><span>'.$new_size.'<span></td>';
                        $json_array[0]['opti_size']         = $opti_size;
                        $json_array[0]['optimised_num']     = $optimised + 1;
                        $json_array[0]['not_optimised_num'] = $data['number_of_not_optimised'] - 1;

                    }else{
                        echo json_encode('Error. Please, check help page.');
                    }
                }
                echo json_encode($json_array);
            }
        }

        $this->response->setOutput($this->load->view('module/dfdesign_optimg.tpl', $data));
    }

    public static function getAllImages(){
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DIR_IMAGE . 'cache'), RecursiveIteratorIterator::CHILD_FIRST);
        $files = array();
        $size = 0;

        foreach ($iterator as $path) {
            if ($path->isFile()) {
                $filename = $path->__toString();
                if(strtolower(pathinfo($filename,PATHINFO_EXTENSION)) ==='jpeg' ||
                strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'jpg' ||
                strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'png'){
                    
                    $filesize = filesize($filename);
                    $size += $filesize;
                    $files[] = array(
                        'name'  => $filename,
                        'size'  =>  $filesize
                        );
                }
            }
        }
        return array(
            'total' => $size,
            'files' => $files
            );
    }

    public static function getOptimisedImages() {
        $cached_file = DIR_IMAGE . 'cache/optimised.dat';
        if (!file_exists($cached_file)) {
            $files = '';
            return $files;
        }
        $size = 0;
        $files = explode('\r\n', file_get_contents($cached_file));
        return $files;
    }

    public function getAllFiles(){
        $all_images = self::getAllImages();
        $all_files = $all_images['files'];
        $all_files_in_folder = count($all_files);
    }

    public function getAlreadyOptimisedFiles(){
        $optimised_images = self::getOptimisedImages();
        $optimised_files = $optimised_images;
        $optimised = count($optimised_files);

        if($optimised_files == ''){
            $optimised =0;
        }
    }

}