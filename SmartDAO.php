<?php

class DAO {

    static $con;
    static $uuid = "UUID()";

    /**
     * Bu sınıfın veritabanına bağlanmasını sağlayan metod.
     * @return boolean Bağlantı başarılı veya değil. true/false
     */
    static function connect() {
        $saglam = DAO::$con = new mysqli("host", "user", "password", "database");
        if (mysqli_connect_errno()) {
//            printf("Connect failed: %s\n", mysqli_connect_error());
//            exit();
        }

        mysqli_query(DAO::$con, "SET NAMES 'utf8'");
        mysqli_query(DAO::$con, "SET CHARACTER SET 'utf8'");
        mysqli_query(DAO::$con, "SET time_zone = '+2:00'"); // Kışın +2
        mysqli_query(DAO::$con, "SET COLLATION_CONNECTION = 'utf8_unicode_ci'");
        return $saglam;
    }

    /**
     * Veritabanından değer seçer ve JSON olarak dizi dönderir.<br>
     * Örn: $dao->select("tablo");<br>
     * Örn: $dao->select("tablo","sutun");<br>
     * Örn: $dao->select("tablo","sutun","id<4");<br>
     * Örn: $dao->select("tablo","sutun","id<4","id desc");<br>
     * Örn: $dao->select("tablo","sutun","id<4","id desc",5);<br>
     * Örn: $dao->select("tablo","sutun","id<4","id desc",5,10);<br>
     * @param String $tablo   Veri alınacak olan tablo.
     * @param String $sutun   Seçilecek sütunlar. Örn: "adi,soyadi" (Yazılmayabilir=*)
     * @param Array,String $sart    Seçilecek verileri kısıtlar. (Yazılmayabilir)
     * @param Integer $order   Seçilecek verilerin sıralanması. Örn: "id desc" veya "name asc" (Yazılmayabilir)
     * @param Integer $limit   Kaç veri isteniyorsa. (Yazılmayabilir)
     * @param Integer $offset  Seçilecek veriler kaçıncı kayıttan sonra başlayacak. (Yazılmayabilir)
     * @return type JSON dizisi olarak veritabanı tablosunu gönderir.
     */
    static function select($tablo, $sutun = "*", $sart = null, $order = null, $limit = null, $offset = null) {

        DAO::connect();
        $sql = "select $sutun from $tablo";
        if (isset($sart)) {
            $sql.=DAO::sart($sart);
        }
        if (isset($order) && is_string($order))
            $sql.=" order by $order";
        if (is_numeric($limit))
            $sql.=" LIMIT  $limit";
        if (is_numeric($offset))
            $sql.=" OFFSET  $offset";
        return DAO::run($sql);
    }

    static function execute($query) {
        DAO::connect();
        return DAO::run($query);
    }

    static function get_affected_rows() {
        return mysqli_affected_rows(DAO::$con);
    }

    static function call($procedure, $veri) {

        DAO::connect();
        $params = "";
        $ilk = false;
        foreach ($veri as $deger) {
            if ($ilk) {
                $params.=",";
            }
            $params .=is_string($deger) ? "'" . mysqli_real_escape_string(DAO::$con, $deger) . "'" : "$deger";
            $ilk = true;
        }
        $sql = "call $procedure($params)";
        return DAO::run($sql);
    }

    static function func($func, $veri) {

        DAO::connect();
        $params = "";
        $ilk = false;
        foreach ($veri as $deger) {
            if ($deger) {
                if ($ilk) {
                    $params.=", ";
                }
                $params .=is_string($deger) ? "'" . mysqli_real_escape_string(DAO::$con, $deger) . "'" : "$deger";
                $ilk = true;
            }
        }
        $sql = "select $func($params) as val;";
        return DAO::run($sql);
    }

    static function count($tablo, $sart = null) {

        DAO::connect();
        $sql = "select count(*) as count from $tablo";
        if (isset($sart)) {
            $sql.=DAO::sart($sart);
        }
        return DAO::run($sql);
    }

    /**
     * Veritabanına kayıt ekleme.
     * @param String $tablo   Kayıt eklenecek tablo.
     * @param Array $veri    Eklenecek kayıtlar dizisi. Örn: array("name"=>"Ahmet")
     * @param type $getID   Yeni kaydın ID'sini dönderir.
     * @return type         mysql_query() metodunun karşılığı gönderilir. if içinde kullanılabilir.
     */
    static function insert($tablo, $veri, $ignore = false) {

        DAO::connect();
        $columns = "";
        $values = "";
        $ilk = false;
        foreach ($veri as $anahtar => $deger) {

            if ($ilk) {
                $columns.=", ";
                $values.=", ";
            }
            $columns .=$anahtar;
            if ($deger === "UUID()" || $deger === "uuid()" || $deger === "now()" || $deger === "NOW()" || strpos($deger, "get") === 0 || is_numeric($deger)) {
                $values .=$deger;
            } else if ($deger) {
                $values .=is_string($deger) ? "'" . mysqli_real_escape_string(DAO::$con, $deger) . "'" : "$deger";
            } else {
                $values .="null";
            }
            $ilk = true;
        }
        //" => &#34;
        $sql = "insert ";
        if ($ignore)
            $sql .= "ignore ";
        $sql .= "into $tablo ($columns) values ($values);";
//        echo $sql.""
        return DAO::run($sql);
    }

    /**
     * Tabloda veri güncelleme metodu.
     * @param type $tablo   Verisi güncellenecek tablo.
     * @param Array $veri    Güncellenecek veriler dizisi. Örn: array("name"=>"Mehmet")
     * @param Array,String $sart    Güncellenecek veriyi seçmek için oluşturulan şart.
     * @return type         mysql_query() metodunun karşılığı gönderilir. if içinde kullanılabilir.
     */
    static function update($tablo, $veri, $sart) {

        DAO::connect();
        $sql = "update $tablo set ";
        $ilk = false;
        foreach ($veri as $anahtar => $deger) {
            if ($deger === "UUID()" || $deger === "uuid()" || $deger === "now()" || $deger === "NOW()" || strpos($deger, "get") === 0 || is_numeric($deger)) {
                $sql .=($ilk) ? ", " : "";
                $sql .="$anahtar=$deger";
            } else if ($deger) {
                $sql .=($ilk) ? ", " : "";
                $sql .="$anahtar=";
                $sql .=(is_string($deger) ? "'" . mysqli_real_escape_string(DAO::$con, $deger) . "'" : "$deger");
            } else {
                $sql .=($ilk) ? ", " : "";
                $sql .="$anahtar=null";
            }
            $ilk = true;
        }
        $sql.=DAO::sart($sart);
        return DAO::run($sql, true);
    }

    /**
     * Tabloda veri arttırma metodu.
     * DAO::increase("book_counter","reader","uid=5");
     * @param type $tablo   Verisi güncellenecek tablo.
     * @param String $sutun    Güncellenecek sutun
     * @param Array,String $sart    Güncellenecek veriyi seçmek için oluşturulan şart.
     * @return type         mysql_query() metodunun karşılığı gönderilir. if içinde kullanılabilir.
     */
    static function increase($tablo, $sutun, $sart) {
        DAO::connect();
        $sql = "update $tablo set $sutun=$sutun+1";
        $sql.=DAO::sart($sart);
        return DAO::run($sql, true);
    }

    /**
     * Tabloda veri azaltma metodu.
     * DAO::decrease("book_counter","reader","uid=5");
     * @param type $tablo   Verisi güncellenecek tablo.
     * @param String $sutun    Güncellenecek sutun
     * @param Array,String $sart    Güncellenecek veriyi seçmek için oluşturulan şart.
     * @return type         mysql_query() metodunun karşılığı gönderilir. if içinde kullanılabilir.
     */
    static function decrease($tablo, $sutun, $sart) {
        DAO::connect();
        $sql = "update $tablo set $sutun=$sutun-1";
        $sql.=DAO::sart($sart);
        return DAO::run($sql, true);
    }

    /**
     * Veri silme metodu.
     * @param String $tablo   Veri silinecek tablonun adı.
     * @param Array,String $sart    Silinecek veri şartı.
     * @return type mysql_query() metodunun karşılığı gönderilir. if içinde kullanılabilir.
     */
    static function delete($tablo, $sart = null) {

        DAO::connect();
        $sql = "delete from $tablo" . DAO::sart($sart);
        return DAO::run($sql, true);
    }

    static function run($sql, $returnAffectedRows = false) {
        try {
//            echo $sql.PHP_EOL;
            $query = mysqli_query(DAO::$con, $sql); //or die(mysqli_error(DAO::$con));

            if ($query instanceof mysqli_result) {// SELECT sorgusu çalışırsa
                $arr = array();
                $index = 0;
                while ($row = mysqli_fetch_assoc($query)) {
                    $arr[$index] = $row;
                    $index++;
                }
                DAO::close();
                return $arr;
            } else if ($returnAffectedRows) { // Etkilenen satır sayısı istenirse veya sorgu başarılıysa (update/delete için)
                $afr = mysqli_affected_rows(DAO::$con);
                DAO::close();
                return $afr >= 0;
            } else { // Geriye insert kalıyor, kayıt edilen satırın ID'si döner
                $lid = mysqli_insert_id(DAO::$con);
                DAO::close();
                return $lid;
            }
        } catch (Exception $exc) {
            logWarn("SQL ERROR: $exc Sorgu: $sql");
        }
        $arr = array();
        return $arr;
    }

    static function close() {
        mysqli_close(DAO::$con);
    }

    static function sart($sart) {
        $sql = "";
        if (is_string($sart)) {
            $sql.=" where $sart";
        } elseif (is_array($sart)) {
            $sql.=" where ";
            $ilk = false;
            foreach ($sart as $anahtar => $deger) {
                if ($ilk == true) {
                    $sql.=" and ";
                }
                if (is_string($deger)) {
                    $sql.=$anahtar . " like '" . mysqli_real_escape_string(DAO::$con, $deger) . "'";
                } else {
                    $sql.=$anahtar . " like " . mysqli_real_escape_string(DAO::$con, $deger);
                }
                $ilk = true;
            }
        }
        return $sql;
    }

}

?>
