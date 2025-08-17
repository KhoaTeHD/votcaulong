<?php
/**
 * Plugin Name: Currency & Interest Shortcodes
 * Description: Shortcodes for currency converter, exchange rate table, interest rate table, and calculators.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base URL for flag SVGs
 */
function cc_get_flag_base_url() {
    return 'https://www.vietcombank.com.vn/-/media/Default-Website/Default-Images/Icons/Flags/im_flag_';
}

/**
 * Static fallback exchange data
 */
function cc_get_exchange_static() {
    return [
        'USD'=>['name'=>'US DOLLAR','buy'=>25830.00,'transfer'=>25860.00,'sell'=>26220.00],
        'EUR'=>['name'=>'EURO','buy'=>28963.74,'transfer'=>29256.30,'sell'=>30550.67],
        'GBP'=>['name'=>'UK POUND STERLING','buy'=>34337.83,'transfer'=>34684.67,'sell'=>35796.00],
        'JPY'=>['name'=>'JAPANESE YEN','buy'=>174.51,'transfer'=>176.28,'sell'=>185.60],
        'AUD'=>['name'=>'AUSTRALIAN DOLLAR','buy'=>16459.73,'transfer'=>16625.99,'sell'=>17158.70],
        'SGD'=>['name'=>'SINGAPORE DOLLAR','buy'=>19715.50,'transfer'=>19914.65,'sell'=>20593.89],
        'THB'=>['name'=>'THAI BAHT','buy'=>704.47,'transfer'=>782.75,'sell'=>815.95],
        'CAD'=>['name'=>'CANADIAN DOLLAR','buy'=>18556.05,'transfer'=>18743.49,'sell'=>19344.05],
        'CHF'=>['name'=>'SWISS FRANC','buy'=>30889.87,'transfer'=>31201.88,'sell'=>32201.62],
        'HKD'=>['name'=>'HONG KONG DOLLAR','buy'=>3224.75,'transfer'=>3257.33,'sell'=>3381.93],
        'CNY'=>['name'=>'CHINESE YUAN','buy'=>3532.92,'transfer'=>3568.61,'sell'=>3682.95],
        'DKK'=>['name'=>'DANISH KRONE','buy'=>null,'transfer'=>3911.83,'sell'=>4061.48],
        'INR'=>['name'=>'INDIAN RUPEE','buy'=>null,'transfer'=>301.82,'sell'=>314.82],
        'KRW'=>['name'=>'KOREAN WON','buy'=>16.60,'transfer'=>18.45,'sell'=>20.01],
    ];
}

/**
 * Retrieve exchange data from Vietcombank XML (cache 10m), fallback to static
 */
function cc_get_exchange_data() {
    $key = 'cc_currency_data';
    if ( false !== ( $cached = get_transient( $key ) ) ) {
        return $cached;
    }
    $api_url = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';
    $resp = wp_remote_get( $api_url, [ 'timeout' => 15 ] );
    if ( is_wp_error( $resp ) ) {
        $data = [ 'datetime' => current_time('d/m/Y H:i'), 'rates' => cc_get_exchange_static() ];
        set_transient( $key, $data, 600 );
        return $data;
    }
    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );
    if ( 200 !== intval( $code ) || empty( $body ) ) {
        $data = [ 'datetime' => current_time('d/m/Y H:i'), 'rates' => cc_get_exchange_static() ];
        set_transient( $key, $data, 600 );
        return $data;
    }
    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $body );
    if ( ! $xml ) {
        libxml_clear_errors();
        $data = [ 'datetime' => current_time('d/m/Y H:i'), 'rates' => cc_get_exchange_static() ];
        set_transient( $key, $data, 600 );
        return $data;
    }
    $datetime_raw = isset($xml->DateTime) ? (string)$xml->DateTime : '';
    $rates = [];
    foreach ( $xml->Exrate as $node ) {
        $c = (string)$node['CurrencyCode'];
        $rates[ $c ] = [
            'name'     => trim((string)$node['CurrencyName']),
            'buy'      => ('-'===(string)$node['Buy'])?null:floatval(str_replace(',','',(string)$node['Buy'])),
            'transfer' => ('-'===(string)$node['Transfer'])?null:floatval(str_replace(',','',(string)$node['Transfer'])),
            'sell'     => ('-'===(string)$node['Sell'])?null:floatval(str_replace(',','',(string)$node['Sell'])),
        ];
    }
    $data = [ 'datetime' => $datetime_raw, 'rates' => $rates ];
    set_transient( $key, $data, 600 );
    return $data;
}

/**
 * Shortcode: [currency_table] – converter + exchange rate table
 */
function cc_render_currency_converter( $atts ) {
    $data = cc_get_exchange_data();
    if ( is_wp_error( $data ) ) {
        return '<div class="cc-error">Lỗi tỷ giá: '.esc_html($data->get_error_message()).'</div>';
    }
    $datetime_raw = $data['datetime'];
    $rates = $data['rates'];
    $last_updated = '';
    if ( $datetime_raw && ( $ts = strtotime( $datetime_raw ) ) ) {
        $last_updated = date_i18n( 'H:i \\n\gà\y d/m/Y', $ts );
    }
    $flag_base = cc_get_flag_base_url();
    $priority_codes = ['USD','EUR','GBP','JPY','CNY','AUD','CAD','CHF','HKD','SGD','THB','KRW','MYR','INR','RUB'];
    $ordered = [];
    foreach ( $priority_codes as $c ) {
        if ( isset( $rates[$c] ) ) {
            $ordered[] = $c;
        }
    }
    $remaining = array_diff( array_keys($rates), $priority_codes );
    sort( $remaining );
    $ordered = array_merge( $ordered, $remaining );
    $js_rates     = wp_json_encode( $rates );
    $js_flag_base = wp_json_encode( $flag_base );
    $js_priority  = wp_json_encode( $priority_codes );
    ob_start(); ?>
    <div id="cc-container">
      <div id="cc-tabs">
        <button id="cc-tab-buy" class="active" data-mode="buy">Mua tiền mặt</button>
        <button id="cc-tab-sell" data-mode="sell">Bán</button>
      </div>
      <div id="cc-converter">
        <?php if ( $last_updated ): ?>
          <div id="cc-last-updated">Cập nhật: <?php echo esc_html( $last_updated ); ?></div>
        <?php endif; ?>
        <div class="cc-row">
          <div class="cc-currency-select">
            <img id="cc-flag-left" src="" alt="flag" onerror="this.style.display='none';" />
            <select id="cc-select-left"></select>
          </div>
          <div class="cc-amount-input">
            <input id="cc-input-left" type="text" placeholder="0" />
          </div>
        </div>
        <div class="cc-arrow">→</div>
        <div class="cc-row">
          <div class="cc-currency-select">
            <img src="https://www.vietcombank.com.vn/-/media/Default-Website/Default-Images/Icons/Flags/VN-Vietnam-Flag-icon.svg" alt="VND flag" onerror="this.style.display='none';" />
            <select disabled><option>VND</option></select>
          </div>
          <div class="cc-amount-input">
            <input id="cc-input-right" type="text" value="0" readonly />
          </div>
        </div>
      </div>
      <table id="cc-rate-table" class="cc-table">
        <thead>
          <tr><th>Mã</th><th>Tên</th><th>Mua</th><th>Bán</th></tr>
        </thead>
        <tbody>
          <?php foreach ( $ordered as $code ):
            $info     = $rates[ $code ];
            $clower   = ( strtolower($code) === 'usd' ? 'usa' : strtolower($code) );
            $flag_url = esc_url( $flag_base . $clower . '.svg' );
            $buy_text = is_null($info['buy']) ? '–' : number_format_i18n($info['buy'],2);
            $sell_text= is_null($info['sell'])? '–' : number_format_i18n($info['sell'],2);
          ?>
          <tr>
            <td class="cc-flag-cell"><img src="<?php echo $flag_url; ?>" alt="<?php echo esc_attr($code); ?> flag" onerror="this.style.display='none';" /><?php echo esc_html($code); ?></td>
            <td><?php echo esc_html($info['name']); ?></td>
            <td><?php echo esc_html($buy_text); ?></td>
            <td><?php echo esc_html($sell_text); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <script>
    jQuery(function($){
        var ccRates      = <?php echo $js_rates;?>,
            flagBaseUrl  = <?php echo $js_flag_base;?>,
            priorityCodes= <?php echo $js_priority;?>,
            mode='buy';
        function populateDropdown(){
            var $s=$('#cc-select-left'); $s.empty();
            priorityCodes.forEach(function(c){
                if(ccRates[c] && ((mode==='buy' && ccRates[c].buy!==null) || (mode==='sell' && ccRates[c].sell!==null))){
                    $s.append($('<option>').val(c).text(c));
                }
            });
            Object.keys(ccRates).filter(c=>!priorityCodes.includes(c)).sort().forEach(function(c){
                if((mode==='buy' && ccRates[c].buy!==null) || (mode==='sell' && ccRates[c].sell!==null)){
                    $s.append($('<option>').val(c).text(c));
                }
            });
            updateFlag();
        }
        function updateFlag(){
            var c=$('#cc-select-left').val(), img=$('#cc-flag-left');
            if(!c){ img.hide(); return; }
            img.show();
            var cl=c.toLowerCase()==='usd'?'usa':c.toLowerCase();
            img.attr('src', flagBaseUrl + cl + '.svg');
        }
        function formatNum(n){ return isNaN(n)?'':n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
        function recalc(){
            var v=parseFloat($('#cc-input-left').val().replace(/,/g,''))||0,
                c=$('#cc-select-left').val(),
                rate=mode==='buy'? ccRates[c].buy : ccRates[c].sell;
            $('#cc-input-right').val(rate===null?'0':formatNum(v*rate));
        }
        function switchMode(m){
            mode=m;
            $('#cc-tabs button').removeClass('active');
            $('#cc-tab-'+(m==='buy'?'buy':'sell')).addClass('active');
            $('#cc-input-left,#cc-input-right').val('');
            populateDropdown();
        }
        $('#cc-tabs button').on('click', function(){ switchMode($(this).data('mode')); });
        $('#cc-select-left').on('change', function(){ updateFlag(); $('#cc-input-left,#cc-input-right').val(''); });
        $('#cc-input-left').on('input', function(){ this.value=this.value.replace(/[^0-9\.]/g,''); recalc(); });
        switchMode('buy');
    });
    </script>
<?php }
add_shortcode('currency_table','cc_render_currency_converter');

/**
 * Retrieve interest rates (ACF or fallback)
 */
function cc_get_interest_rates(){
    if(function_exists('get_field')){
        $rows=get_field('interest_rates','option');
        if(is_array($rows)){
            $out=[];
            foreach($rows as $r){
                $item=['term'=>$r['term']];
                foreach($r as $k=>$v){
                    if(strpos($k,'rate_')===0){
                        $c=strtoupper(str_replace('rate_','',$k));
                        $item[$c]=floatval($v);
                    }
                }
                $out[]=$item;
            }
            return $out;
        }
    }
    return [
        ['term'=>'Không kỳ hạn','VND'=>0.10,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'7 ngày','VND'=>0.20,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'14 ngày','VND'=>0.20,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'1 tháng','VND'=>1.60,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'2 tháng','VND'=>1.60,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'3 tháng','VND'=>1.90,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'6 tháng','VND'=>2.90,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'9 tháng','VND'=>2.90,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'12 tháng','VND'=>4.60,'EUR'=>0.30,'USD'=>0.00],
        ['term'=>'24 tháng','VND'=>4.70,'EUR'=>0.30,'USD'=>0.00],
    ];
}

/**
 * Shortcode [interest_table] – dynamic interest rate table
 */
function cc_interest_table_shortcode(){
    $rates=cc_get_interest_rates();
    if(empty($rates)) return '<p>Chưa có dữ liệu lãi suất.</p>';
    $cols=[];
    foreach($rates as $r){
        foreach($r as $k=>$v){
            if($k!=='term' && !in_array($k,$cols,true)) $cols[]=$k;
        }
    }
    if(false!==($i=array_search('VND',$cols))){array_splice($cols,$i,1);array_unshift($cols,'VND');}
    ob_start(); ?>
    <table class="cc-table cc-interest-table">
      <thead><tr><th>Kỳ hạn</th><?php foreach($cols as $c):?><th><?php echo esc_html($c);?></th><?php endforeach;?></tr></thead>
      <tbody><?php foreach($rates as $r):?><tr><td><?php echo esc_html($r['term']);?></td><?php foreach($cols as $c):?><td><?php echo number_format_i18n($r[$c]??0,2).'%';?></td><?php endforeach;?></tr><?php endforeach;?></tbody>
    </table>
    <?php return ob_get_clean();
}
add_shortcode('interest_table','cc_interest_table_shortcode');

/**
 * Shortcode: [interest_calculator]
 * Calculator lãi cơ bản + crypto
 */
function cc_interest_calculator_shortcode() {
    $rates = cc_get_interest_rates();
    if ( empty($rates) ) return '<p>Chưa có dữ liệu để tính lãi.</p>';

     // Terms
    $terms = array_column($rates,'term');
    // Only VND and USD
    $currs = ['VND','USD'];
    // Prepare JS data
    $vnd_rates     = [];
    foreach($rates as $r) {
        $vnd_rates[$r['term']] = floatval($r['VND'] ?? 0);
    }
    $fx = cc_get_exchange_data();
    $usd_to_vnd = $fx['rates']['USD']['transfer'] ?? 1;
    $symbols  = ['BTC'=>'BTCUSDT','ETH'=>'ETHUSDT','USDT'=>null];
    $crypto   = [];
    foreach($symbols as $c=>$sym) {
        $crypto[$c] = 1;
        if($sym) {
            $res = wp_remote_get("https://fapi.binance.com/fapi/v1/ticker/24hr?symbol={$sym}");
            if(!is_wp_error($res)) {
                $d = json_decode(wp_remote_retrieve_body($res), true);
                $crypto[$c] = isset($d['lastPrice']) ? floatval($d['lastPrice']) : 1;
            }
        }
    }

    $js_terms      = wp_json_encode($terms);
    $js_vnd_rates  = wp_json_encode($vnd_rates);
    $js_usd_to_vnd = wp_json_encode($usd_to_vnd);
    $js_crypto     = wp_json_encode($crypto);

    ob_start(); ?>
    <div id="cc-container">
      <div id="cc-converter" class="interest-calc-box">
        <div id="cc-interest-calc">
          <div class="box left-col">
            <label>Loại tiền</label>
            <select id="cc-cur"><?php foreach($currs as $c):?><option><?php echo esc_html($c);?></option><?php endforeach;?></select>
            <label>Số tiền gửi</label>
            <input id="cc-amt" type="text" placeholder="0" />
            <label>Kỳ hạn</label>
            <select id="cc-term"><?php foreach($terms as $t):?><option><?php echo esc_html($t);?></option><?php endforeach;?></select>
          </div>
          <div class="box right-col">
            <h4>Tiền lãi dự tính</h4>
            <div class="cc-total_amount d-flex justify-content-space">Số tiền lãi: <div><span id="cc-int">0</span> <span id="cc-cur1">VND</span></div></div>
            <div class="cc-total_amount d-flex justify-content-space">Tổng tiền: <div><span id="cc-total">0</span> <span id="cc-cur2">VND</span></div></div>
            <div class="cc-apply-rate d-flex justify-content-space">Lãi suất áp dụng: <div><span id="cc-rate">0</span>%</div></div>
            <hr />
            <div class="cc-total_amount_coin cc-total_amount_btc d-flex justify-content-space">Lãi theo BTC: <div><span id="cc-int-btc">0</span> BTC</div></div>
            <div class="cc-total_amount_coin cc-total_amount_eth d-flex justify-content-space">Lãi theo ETH: <div><span id="cc-int-eth">0</span> ETH</div></div>
            <div class="cc-total_amount_coin cc-total_amount_usdt d-flex justify-content-space">Lãi theo USDT: <div><span id="cc-int-usdt">0</span> USDT</div></div>
          </div>
        </div>
      </div>
    </div>
    <script>
    jQuery(function($){
      var terms     = <?php echo $js_terms;?>,
          vndRates  = <?php echo $js_vnd_rates;?>,
          usdToVnd  = <?php echo $js_usd_to_vnd;?>,
          crypto    = <?php echo $js_crypto;?>;
      function calc(){
        // format input
        var raw = $('#cc-amt').val().replace(/[^0-9]/g,'');
        var num = parseInt(raw)||0;
        $('#cc-amt').val(num.toLocaleString('vi-VN'));

        var cur   = $('#cc-cur').val();
        var amt   = num;
        var term  = $('#cc-term').val();
        var rate  = vndRates[term] || 0;

        var days=0, mons=0;
        if(/ngày/i.test(term)) days=parseFloat(term)||0;
        else if(/tháng/i.test(term)) mons=parseFloat(term)||0;

        var intVND = days>0
          ? amt*(rate/100)*(days/360)
          : mons>0
            ? amt*(rate/100)*(mons/12)
            : amt*(rate/100)/12;

        var totVND  = amt + intVND;
        var intUSD  = intVND / usdToVnd;

        function fmt(x,d){return x.toLocaleString('vi-VN',{minimumFractionDigits:d,maximumFractionDigits:d});}

        $('#cc-int').text(fmt(intVND,0));
        $('#cc-total').text(fmt(totVND,0));
        $('#cc-rate').text(rate.toFixed(2));
        $('#cc-cur1,#cc-cur2').text(cur);

        $('#cc-int-btc').text(fmt(intUSD/crypto.BTC,6));
        $('#cc-int-eth').text(fmt(intUSD/crypto.ETH,6));
        $('#cc-int-usdt').text(fmt(intUSD/crypto.USDT,6));
      }
      $('#cc-cur,#cc-amt,#cc-term').on('change input',calc);
      calc();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('interest_calculator','cc_interest_calculator_shortcode');


/**
 * Shortcode [interest_calculator_crypto] – VND→USD→crypto
 */
function cc_interest_calculator_crypto_shortcode(){
    $rates=cc_get_interest_rates();
    if(empty($rates)) return '<p>Chưa có dữ liệu lãi suất.</p>';
    $fx=cc_get_exchange_data();
    $usd_trans=is_wp_error($fx)||!isset($fx['rates']['USD']['transfer'])?1:$fx['rates']['USD']['transfer'];
    $symbols=['BTC'=>'BTCUSDT','ETH'=>'ETHUSDT','USDT'=>null];
    $crypto=[];
    foreach($symbols as $coin=>$sym){
        if($sym){$res=wp_remote_get("https://fapi.binance.com/fapi/v1/ticker/24hr?symbol={$sym}");$data=!is_wp_error($res)?json_decode(wp_remote_retrieve_body($res),true):[];$crypto[$coin]=isset($data['lastPrice'])?floatval($data['lastPrice']):1;}else $crypto[$coin]=1;
    }
    $terms=array_column($rates,'term');
    $vnd_rates=[];foreach($rates as $r)$vnd_rates[$r['term']]=floatval($r['VND']??0);
    ?>
    <div id="cc-cryptocalc">
      <div class="box">
        <label>Số tiền gửi (VND)</label>
        <input id="cc-amt" type="number" placeholder="0" />
        <label>Kỳ hạn</label>
        <select id="cc-term"><?php foreach($terms as $t):?><option><?php echo esc_html($t);?></option><?php endforeach;?></select>
        <label>Chuyển sang</label>
        <select id="cc-out"><option>VND</option><option>USD</option><option>BTC</option><option>ETH</option><option>USDT</option></select>
      </div>
      <div class="box">
        <div>Lãi dự tính: <span id="cc-int">0</span> <span id="cc-cur1">VND</span></div>
        <div>Tổng tiền: <span id="cc-total">0</span> <span id="cc-cur2">VND</span></div>
        <div>(Rate VND: <span id="cc-rate">0</span>% / USD↔VND: <span id="cc-usd">0</span>)</div>
      </div>
    </div>
    <script>
    jQuery(function($){
      var vndRates=<?php echo wp_json_encode($vnd_rates);?>,
          usdTrans=<?php echo wp_json_encode($usd_trans);?>,
          crypto=<?php echo wp_json_encode($crypto);?>;
      function calc(){
        var amt=parseFloat($('#cc-amt').val())||0,
            term=$('#cc-term').val(),
            out=$('#cc-out').val(),
            rate=vndRates[term]||0,
            days=0,mons=0;
        if(/ngày/i.test(term)) days=parseFloat(term)||0; else if(/tháng/i.test(term)) mons=parseFloat(term)||0;
        var intVND = days>0? amt*(rate/100)*(days/360) : mons>0? amt*(rate/100)*(mons/12) : amt*(rate/100)/12;
        var totVND = amt+intVND,
            intUSD  = intVND/usdTrans,
            totUSD  = totVND/usdTrans,
            disp,tot,cur;
        if(out==='VND'){disp=intVND;tot=totVND;cur='VND';}
        else if(out==='USD'){disp=intUSD;tot=totUSD;cur='USD';}
        else{var px=crypto[out]||1;disp=intUSD/px;tot=totUSD/px;cur=out;}
        function fmt(x){return x.toLocaleString('vi-VN',{minimumFractionDigits:4,maximumFractionDigits:4});}
        $('#cc-int').text(fmt(disp));$('#cc-total').text(fmt(tot));$('#cc-cur1,#cc-cur2').text(cur);
        $('#cc-rate').text(rate.toFixed(2));$('#cc-usd').text(usdTrans.toLocaleString());
      }
      $('#cc-amt,#cc-term,#cc-out').on('change input',calc);
      calc();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('interest_calculator_crypto','cc_interest_calculator_crypto_shortcode');

/**
 * Shortcode: [crypto_rate_table] – crypto price table
 */
function cc_crypto_rate_table_shortcode() {
    $symbols = [
        'BTC' => 'BTCUSDT',
        'ETH' => 'ETHUSDT',
        'USDT'=> null
    ];
    $crypto = [];
    foreach ( $symbols as $coin => $symbol ) {
        if ( $symbol ) {
            $res = wp_remote_get( "https://fapi.binance.com/fapi/v1/ticker/24hr?symbol={$symbol}" );
            if ( ! is_wp_error( $res ) ) {
                $data = json_decode( wp_remote_retrieve_body( $res ), true );
                $crypto[ $coin ] = isset( $data['lastPrice'] ) ? floatval( $data['lastPrice'] ) : 0;
            }
        } else {
            $crypto[ $coin ] = 1;
        }
    }
    // USD to VND rate
    $fx = cc_get_exchange_data();
    $usd_to_vnd = isset( $fx['rates']['USD']['transfer'] ) ? $fx['rates']['USD']['transfer'] : 1;

    ob_start();
    ?>
    <table class="cc-table">
      <thead>
        <tr>
          <th>Coin</th>
          <th>Price (USD)</th>
          <th>Price (VND)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $crypto as $coin => $price_usd ) : 
            $price_vnd = $price_usd * $usd_to_vnd; ?>
          <tr>
            <td><?php echo esc_html( $coin ); ?></td>
            <td><?php echo number_format_i18n( $price_usd, 2 ); ?></td>
            <td><?php echo number_format_i18n( $price_vnd, 0 ); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode( 'crypto_rate_table', 'cc_crypto_rate_table_shortcode' );
/**
 * Add common CSS to head
 */
function cc_add_common_styles(){ ?>
<style>
    :root{
        --table-main-color:#104424;
        --cc-orange:#ff8c00;
        --cc-red:#ff0000;
        --cc-gray:#999;
        --cc-lightgray:#eee;
        --cc-white:#fff;
        --cc-black:#000;
    }
/* COMMON TABLE */
.cc-table{width:100%;border-collapse:collapse;margin-top:24px;font-size:14px;font-family:Arial,sans-serif}
.cc-table th,.cc-table td{border:1px solid #DDD;padding:8px;text-align:center;vertical-align:middle}
.cc-table th{background-color:var(--table-main-color);color:#FFF}
.cc-table tr:nth-child(even){background-color:#F9F9F9}
.cc-table .cc-flag-cell{display:flex;align-items:center;justify-content:flex-start;padding-left:8px}
.cc-table .cc-flag-cell img{margin-right:6px;width:20px;height:auto}
/* CONVERTER */
.cc-container{max-width:600px;margin:0 auto 40px;font-family:Arial,sans-serif}
#cc-tabs{display:flex;margin-bottom:12px}
#cc-tabs button{flex:1;padding:8px 0;border:none;cursor:pointer;font-weight:bold;background-color:#EEE;color:#333;transition:background-color .2s}
#cc-tabs button.active{background-color:var(--table-main-color);color:#FFF}
#cc-tabs button:not(:last-child){margin-right:4px}
#cc-converter{border:1px solid #DDD;padding:15px;border-radius:15px;background: linear-gradient(343deg, #EAF6FF 0%, #F3FFE9 100%);}
.cc-row{display:flex;align-items:center;margin-bottom:16px}
.cc-currency-select{flex:0 0 140px;display:flex;align-items:center;border:1px solid #CCC;border-radius:4px;background-color:#FFF;padding:4px 8px;margin-right:8px}
.cc-currency-select img{width:24px;height:auto;margin-right:6px}
.cc-currency-select select{border:none;outline:none;font-size:14px;flex:1;background:none;cursor:pointer}
.cc-amount-input{flex:1}
.cc-amount-input input{width:100%;padding:6px;border:1px solid #CCC;border-radius:4px;text-align:right;font-size:14px}
.cc-arrow{flex:0 0 24px;text-align:center;font-size:20px;color:var(--table-main-color)}
#cc-last-updated{text-align:right;font-size:13px;color:#555;margin:-12px 0 8px}
/* CALCULATOR */
#cc-interest-calc{display:flex;flex-wrap:wrap;gap:16px;font-family:Arial,sans-serif; }
#cc-interest-calc .box{padding:16px;border-radius:15px;flex:1;min-width:260px}
.box.right-col {
  background-color: #fff;
  padding: 30px !important;
}
.box.left-col{
  padding: 30px !important;
  position: relative;
}
.box.left-col::before {
  content: "";
  background-color: #eefaf7;
  width: 1.5rem;
  height: 1.5rem;
  -webkit-transform: rotate(45deg);
  transform: rotate(45deg);
  position: absolute;
  top: 45%;
  right: -2em;
}
#cc-interest-calc label{display:block;margin-bottom:4px;font-weight:600}
#cc-interest-calc input,#cc-interest-calc select{width:100%;padding:8px;border:1px solid #CCC;border-radius:4px;margin-bottom:12px;background-color: #fff;}
#cc-interest-calc .result{font-size:1.1em;margin-bottom:8px}
#cc-converter.interest-calc-box {
  padding: 0px !important;
}
#cc-interest-calc .result span{font-weight:600}
	.cc-total_amount {
		color:  #104424 ;
	}
	.cc-total_amount span, .cc-total_amount_coin span{ font-weight:bold;}
	.cc-total_amount_coin,.cc-total_amount,.cc-apply-rate {
		font-size: 1.2em;
	}
	.cc-total_amount_btc {
		color: #ff9900;
	}
	.cc-total_amount_eth {
		color: #104424;
	}
	.cc-total_amount_usdt {
		color: #12946c;
	}
  .d-flex{display: flex;}
  .justify-content-space{justify-content: space-between;}
</style>
<?php }
add_action('wp_head','cc_add_common_styles');


//---------------------------------------
    function test_shortcode(){
	    ob_start();
        $eleID = time();
        ?>
        <div data-element_type="widget" data-settings='{"default_state":"expanded","max_items_expended":"one","n_accordion_animation_duration":{"unit":"ms","size":400,"sizes":[]}}' data-widget_type="nested-accordion.default" data-id="<?php echo $eleID?>" class="elementor-element elementor-element-<?php echo $eleID?> elementor-widget elementor-widget-n-accordion">
            <div class="e-n-accordion" aria-label="Accordion. Open links with Enter or Space, close with Escape, and navigate with Arrow Keys">

                <details class="e-n-accordion-item" id="e-n-accordion-item-1610">
                    <summary class="e-n-accordion-item-title" data-accordion-index="1" tabindex="0" aria-expanded="true" aria-controls="e-n-accordion-item-1610">
                        <span class="e-n-accordion-item-title-header"><div class="e-n-accordion-item-title-text"> Item #1 </div></span>
                        <span class="e-n-accordion-item-title-icon"><span class="e-opened"><i aria-hidden="true" class="fas fa-minus"></i></span><span class="e-closed"><i aria-hidden="true" class="fas fa-plus"></i></span>
		                </span>

                    </summary>
                    <div role="region" aria-labelledby="e-n-accordion-item-1610" class="elementor-element elementor-element-c436433 e-con-full e-flex e-con e-child" data-id="c436433" data-element_type="container">
                        <div class="elementor-element elementor-element-12e7771 elementor-widget elementor-widget-text-editor" data-id="12e7771" data-element_type="widget" data-widget_type="text-editor.default">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>								</div>
                    </div>
                </details>


            </div>
        </div>
        <style>
            .fas {
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                display: inline-block;
                font-style: normal;
                font-variant: normal;
                text-rendering: auto;
                line-height: 1;
            }
            .elementor-widget-n-accordion {
                --n-accordion-title-font-size:20px;
                --n-accordion-title-flex-grow:initial;
                --n-accordion-title-justify-content:initial;
                --n-accordion-title-icon-order:-1;
                --n-accordion-border-width:1px;
                --n-accordion-border-color:#d5d8dc;
                --n-accordion-border-style:solid;
                --n-accordion-item-title-flex-grow:initial;
                --n-accordion-item-title-space-between:0px;
                --n-accordion-item-title-distance-from-content:0px;
                --n-accordion-padding:10px;
                --n-accordion-border-radius:0px;
                --n-accordion-icon-size:15px;
                --n-accordion-title-normal-color:#1f2124;
                --n-accordion-title-hover-color:#1f2124;
                --n-accordion-title-active-color:#1f2124;
                --n-accordion-icon-normal-color:var(--n-accordion-title-normal-color);
                --n-accordion-icon-hover-color:var(--n-accordion-title-hover-color);
                --n-accordion-icon-active-color:var(--n-accordion-title-active-color);
                --n-accordion-icon-gap:0 10px;
                width:100%
            }
            .elementor-widget-n-accordion .e-n-accordion details>summary::-webkit-details-marker {
                display:none
            }
            .elementor-widget-n-accordion .e-n-accordion-item {
                display:flex;
                flex-direction:column;
                position:relative
            }
            .elementor-widget-n-accordion .e-n-accordion-item:not(:last-child) {
                margin-block-end:var(--n-accordion-item-title-space-between)
            }
            :where(.elementor-widget-n-accordion .e-n-accordion-item>.e-con) {
                border:var(--n-accordion-border-width) var(--n-accordion-border-style) var(--n-accordion-border-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title {
                align-items:center;
                border-color:var(--n-accordion-border-color);
                border-radius:var(--n-accordion-border-radius);
                border-style:var(--n-accordion-border-style);
                border-width:var(--n-accordion-border-width);
                color:var(--n-accordion-title-normal-color);
                cursor:pointer;
                display:flex;
                flex-direction:row;
                flex-grow:var(--n-menu-title-flex-grow);
                gap:var(--n-accordion-icon-gap);
                justify-content:var(--n-accordion-title-justify-content);
                list-style:none;
                padding:var(--n-accordion-padding)
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-header {
                display:flex
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h1,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h2,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h3,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h4,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h5,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header h6,
            .elementor-widget-n-accordion .e-n-accordion-item-title-header p {
                margin-block-end:0;
                margin-block-start:0
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-text {
                align-items:center;
                font-size:var(--n-accordion-title-font-size)
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-icon {
                align-items:center;
                display:flex;
                flex-direction:row;
                order:var(--n-accordion-title-icon-order);
                position:relative;
                width:-moz-fit-content;
                width:fit-content
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-icon span {
                height:var(--n-accordion-icon-size);
                width:auto
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-icon span>i {
                color:var(--n-accordion-icon-normal-color);
                font-size:var(--n-accordion-icon-size)
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title-icon span>svg {
                fill:var(--n-accordion-icon-normal-color);
                height:var(--n-accordion-icon-size)
            }
            .elementor-widget-n-accordion .e-n-accordion-item-title>span {
                cursor:pointer
            }
            .elementor-widget-n-accordion .e-n-accordion-item[open]>.e-n-accordion-item-title {
                color:var(--n-accordion-title-active-color);
                margin-block-end:var(--n-accordion-item-title-distance-from-content)
            }
            .elementor-widget-n-accordion .e-n-accordion-item[open]>.e-n-accordion-item-title .e-n-accordion-item-title-icon .e-opened {
                display:flex
            }
            .elementor-widget-n-accordion .e-n-accordion-item[open]>.e-n-accordion-item-title .e-n-accordion-item-title-icon .e-closed {
                display:none
            }
            .elementor-widget-n-accordion .e-n-accordion-item[open]>.e-n-accordion-item-title .e-n-accordion-item-title-icon span>i {
                color:var(--n-accordion-icon-active-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item[open]>.e-n-accordion-item-title .e-n-accordion-item-title-icon span>svg {
                fill:var(--n-accordion-icon-active-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item:not([open]):hover>.e-n-accordion-item-title {
                color:var(--n-accordion-title-hover-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item:not([open]):hover>.e-n-accordion-item-title .e-n-accordion-item-title-icon span>i {
                color:var(--n-accordion-icon-hover-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item:not([open]):hover>.e-n-accordion-item-title .e-n-accordion-item-title-icon span>svg {
                fill:var(--n-accordion-icon-hover-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item .e-n-accordion-item-title-icon .e-opened {
                display:none
            }
            .elementor-widget-n-accordion .e-n-accordion-item .e-n-accordion-item-title-icon .e-closed {
                display:flex
            }
            .elementor-widget-n-accordion .e-n-accordion-item .e-n-accordion-item-title-icon span>svg {
                fill:var(--n-accordion-icon-normal-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item .e-n-accordion-item-title-icon span>i {
                color:var(--n-accordion-icon-normal-color)
            }
            .elementor-widget-n-accordion .e-n-accordion-item>span {
                cursor:pointer
            }


        </style>
<?php
	    return ob_get_clean();
    }
add_shortcode('test_shortcode','test_shortcode');