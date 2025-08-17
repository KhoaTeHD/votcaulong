<?php
/**
 * Template Name: Service thu vot
 **/
get_header();
?>
<div class="container my-3">
	<div class="row">
        <div class="col-md-12">
            <section class="my-3 shadow-sm border border-light bg-white">
                <div class="section-header bg-none">
                    <h3 class="title">Dịch vụ thu vợt cũ</h3>
                </div>
                <div class="row p-3">
                    <div class="col-8">
                        <div class="title-danvot">THU MUA VỢT CẦU LÔNG CŨ – NHẬN GIÁ TỐT NHẤT!</div>
                        <div class="title-danvot">Bạn có vợt cầu lông cũ không còn sử dụng? Đừng để chúng lãng phí! Hãy để chúng tôi giúp bạn:</div>
                        <div class="danvot">
                            ✅ Thu mua giá tốt – Định giá nhanh chóng, minh bạch<br>
                            ✅ Nhận vợt tận nơi – Hỗ trợ ship nội thành (tùy khu vực)<br>
                            ✅ Tái sử dụng, bảo vệ môi trường – Vợt của bạn sẽ có cơ hội được sử dụng lại hoặc tái chế đúng cách
                        </div>
                        <div class="title-danvot">Cách thức đăng ký:</div>
                        <div class="danvot">
                            📌 Điền thông tin vào form bên dưới<br>
                            📌 Đính kèm hình ảnh vợt để định giá nhanh<br>
                            📌 Nhận báo giá trong vòng 24h<br>
                            💰 Nhận ngay tiền mặt hoặc chuyển khoản sau khi xác nhận thu mua
                            <div class="register-danvot">
                                <button id="openForm" class="danvot-btn"> ĐĂNG KÝ NGAY </button>
                                <div class="note-danvot">(Nhấn vào nút để điền form)</div>
                            </div>
                            <div id="registerForm" class="form-danvot">
                                <div id="closeForm" class="close-btn">✖</div>
                                <?php echo do_shortcode('[contact-form-7 id="899cb92" title="register-thuvot"]'); ?>
                            </div>
                            </div>
                    </div> 
                    <div class="col-4">
                        <div class="policy-list">
                            <div class="policy-item">
                                <div>
                                    <div class="policy-title">🔒 Chính sách bảo mật</div>
                                </div>
                                <button class="policy-btn" href='#'>Xem</button>
                            </div>

                            <div class="policy-item">
                                <div>
                                    <div class="policy-title">🚚 Chính sách vận chuyển</div>
                                </div>
                                <button class="policy-btn" href='#'>Xem</button>
                            </div>

                            <div class="policy-item">
                                <div>
                                    <div class="policy-title">🛠️ Chính sách bảo hành</div>
                                </div>
                                <button class="policy-btn" href='#'>Xem</button>
                            </div>

                            <div class="policy-item">
                                <div>
                                    <div class="policy-title">💳 Chính sách thanh toán</div>
                                </div>
                                <button class="policy-btn" href='#'>Xem</button>
                            </div>

                            <div class="policy-item">
                                <div>
                                    <div class="policy-title">🛍️ Chính sách bán hàng</div>
                                </div>
                                <button class="policy-btn" href='#'>Xem</button>
                            </div>

                        </div>
                    </div>
                </div>  
            </section>
        </div>
    </div>
</div>
<?php
get_footer();

