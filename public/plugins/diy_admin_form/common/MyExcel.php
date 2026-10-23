<?php


namespace plugins\diy_admin_form\common;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Exception;

class MyExcel
{
    /**
     * 把数据导出到指定的excel模板文件中
     */
    public static function export2excelFile($modelFileName,$outFileName,
                    $fixdata=[],$dataRows=[],$setborders='',$mergeCell=[]){
        try{
            $excelModelFile = "./upload/excel_model/".$modelFileName;
            $file = iconv("utf-8", "gb2312", $excelModelFile);
            if(empty($file) OR !file_exists($file)){
                return '模板文件不存在!!'.$excelModelFile;
            }
            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');
            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');
                if (!$objRead->canRead($file)) {
                    return '只支持导入Excel文件！';
                }
            }

            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            //empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            $obj->setActiveSheetIndex(0);//设置默认工作表
            /* 获取指定的sheet表 */
            $currSheet = $obj->getSheet(0);
            //填充固定数据
            if(count($fixdata)>0){
                foreach ($fixdata as $k=>$v){
                    $currSheet->getCell($k)->setValue($v);
                }
            }
            //填充多行数据
            if(count($dataRows)>0){
                foreach ($dataRows as $rowkey=>$row){
                    if(is_array($row)){
                        foreach ($row as $k=>$v){
                            $currSheet->getCell($k)->setValue($v);
                        }
                    }
                }
            }
            //画单元格线为细线
            if(strlen($setborders)>0){
                $border =[
                    'borders' => [
                        'inside' => ['borderStyle' => Border::BORDER_THIN],
                        'outline' => ['borderStyle' => Border::BORDER_THIN],
//                        'right' => ['borderStyle' => Border::BORDER_THIN],
//                        'bottom' => ['borderStyle' => Border::BORDER_THIN],
                    ]
                ];
                $currSheet->getStyle($setborders)->applyFromArray($border);
            }
            //合并单元格$mergeCell
//            $mystyle = new Style();
//            $mystyle->applyFromArray(
//                [
//                    'fill' =>[
//                        'fillType' => Fill::FILL_SOLID,
//                        'color' => ['argb' => 'FFCCFFCC'],
//                    ]
//                ]
//            );
            foreach ($mergeCell as $k=>$v){
                $currSheet->mergeCells($v);
                //$currSheet->duplicateStyle($mystyle, $v);
            }


            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$outFileName.'"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($obj);
            $writer->save('php://output');
            //删除清空：
            $obj->disconnectWorksheets();
            unset($obj);
            //exit;
            return null;
        }catch (Exception $e){
            var_dump($e);
            return "导出数据时出现错误!";
        }
    }



    /**
     * 导出excel表
     * $data：要导出excel表的数据，接受一个二维数组
     * $name：excel表的表名
     * $head：excel表的表头，接受一个一维数组
     * $key：$data中对应表头的键的数组，接受一个一维数组
     * 备注：此函数缺点是，表头（对应列数）不能超过26；
     *循环不够灵活，一个单元格中不方便存放两个数据库字段的值
     */
    public static function outdata($name = '测试表', $data = [], $head = [], $keys = [])
    {
        $count = count($head);  //计算表头数量
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        for ($i = 65; $i < $count + 65; $i++) {     //数字转字母从65开始，循环设置表头：
            $sheet->setCellValue(strtoupper(chr($i)) . '1', $head[$i - 65]);
        }/*--------------开始从数据库提取信息插入Excel表中------------------*/
        foreach ($data as $key => $item) {
            //循环设置单元格：
            //$key+2,因为第一行是表头，所以写到表格时   从第二行开始写
            for ($i = 65; $i < $count + 65; $i++) {
                //数字转字母从65开始：
                $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]]);
                $spreadsheet
                    ->getActiveSheet()
                    ->getColumnDimension(strtoupper(chr($i)))
                    ->setWidth(20); //固定列宽
            }
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        //删除清空：
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    /**
     * 使用PHPEXECL导入
     * @param string $file      文件地址
     * @param int    $sheet     工作表sheet(传0则获取第一个sheet)
     * @param int    $columnCnt 列数(传0则自动获取最大列)
     * @param array  $options   操作选项
     * array mergeCells 合并单元格数组
     * array formula    公式数组
     * array format     单元格格式数组
     * @return array
     * @throws Exception
     */
    public static function  importExcel($file = '', $sheet = 0, $columnCnt = 0, &$options = []){
        try {
            /* 转码 */
            $file = iconv("utf-8", "gb2312", $file);
            if (empty($file) OR !file_exists($file)) {
                throw new \Exception('文件不存在!');
            }
            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');
            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');
                if (!$objRead->canRead($file)) {
                    throw new \Exception('只支持导入Excel文件！');
                }
            }
            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            /* 获取指定的sheet表 */
            $currSheet = $obj->getSheet($sheet);
            if (isset($options['mergeCells'])) {
                /* 读取合并行列 */
                $options['mergeCells'] = $currSheet->getMergeCells();
            }
            if (0 == $columnCnt) {
                /* 取得最大的列号 */
                $columnH = $currSheet->getHighestColumn();
                /* 兼容原逻辑，循环时使用的是小于等于 */
                $columnCnt = Coordinate::columnIndexFromString($columnH);
            }
            /* 获取总行数 */
            $rowCnt = $currSheet->getHighestRow();
            $data = [];
            /* 读取内容 */
            for ($_row = 1; $_row <= $rowCnt; $_row++) {
                $isNull = true;
                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId = $cellName . $_row;
                    $cell = $currSheet->getCell($cellId);
                    if (isset($options['format'])) {
                        /* 获取格式 */
                        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                        /* 记录格式 */
                        $options['format'][$_row][$cellName] = $format;
                    }
                    if (isset($options['formula'])) {
                        /* 获取公式，公式均为=号开头数据 */
                        $formula = $currSheet->getCell($cellId)->getValue();
                        if (0 === strpos($formula, '=')) {
                            $options['formula'][$cellName . $_row] = $formula;
                        }
                    }
                    if (isset($format) && 'm/d/yyyy' == $format) {
                        /* 日期格式翻转处理 */
                        $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                    }
                    $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());
                    if (!empty($data[$_row][$cellName])) {
                        $isNull = false;
                    }
                }
                /* 判断是否整行数据为空，是的话删除该行数据 */
                if ($isNull) {
                    unset($data[$_row]);
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }


}
