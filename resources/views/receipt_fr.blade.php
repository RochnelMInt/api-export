
<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Laralink">
  <!-- Site Title -->
  <title>{{ $purchase->transaction_id }}</title>
  <link rel="stylesheet" href="{{ asset('invoice_assets/css/style.css')}}">
<!--   <script src="{{ asset('invoice_assets/js/jquery.min.js')}}"></script>
  <script src="{{ asset('invoice_assets/js/jspdf.min.js')}}"></script> -->
  <script>
    function myfunc() {
      console.log('lllll')
    var downloadSection = $('#tm_download_section');
    var cWidth = downloadSection.width();
    var cHeight = downloadSection.height();
    var topLeftMargin = 0;
    var pdfWidth = cWidth + topLeftMargin * 2;
    var pdfHeight = pdfWidth * 1.5 + topLeftMargin * 2;
    var canvasImageWidth = cWidth;
    var canvasImageHeight = cHeight;
    var totalPDFPages = Math.ceil(cHeight / pdfHeight) - 1;
    //var transactionId = $(this).data('transaction_id');

    html2canvas(downloadSection[0], { allowTaint: true }).then(function (
      canvas
    ) {
      canvas.getContext('2d');
      var imgData = canvas.toDataURL('image/png', 1.0);
      var pdf = new jsPDF('p', 'pt', [pdfWidth, pdfHeight]);
      pdf.addImage(
        imgData,
        'PNG',
        topLeftMargin,
        topLeftMargin,
        canvasImageWidth,
        canvasImageHeight
      );
      for (var i = 1; i <= totalPDFPages; i++) {
        pdf.addPage(pdfWidth, pdfHeight);
        pdf.addImage(
          imgData,
          'PNG',
          topLeftMargin,
          -(pdfHeight * i) + topLeftMargin * 0,
          canvasImageWidth,
          canvasImageHeight
        );
      }
      //pdf.save( transactionId + '.pdf');
      pdf.save('download.pdf');
    });
  }
  </script>
</head>

<body>
  <div class="tm_container">
    <div class="tm_invoice_wrap">
      <div class="tm_invoice tm_style1" id="tm_download_section">
        <div class="tm_invoice_in">
          <div class="tm_invoice_head tm_align_center tm_mb20">
            <div class="tm_invoice_left">
              <div class="tm_logo"><img src="https://i.ibb.co/dKyK5p3/media-intelligence-logo.png" alt="Logo"></div>
            </div>
            <div class="tm_invoice_right tm_text_right">
              <div class="tm_primary_color tm_f50 tm_text_uppercase">Facture</div>
              <div class="tm_primary_color tm_f50 tm_text_uppercase">@if($purchase->status == "FAILED" || $purchase->status == "RETURNED" || $purchase->status == "CANCELLED") <span style="color: red;">NON VALIDE</span> @endif</div>
            </div>
          </div>
          <div class="tm_invoice_info tm_mb20">
            <div class="tm_invoice_seperator tm_gray_bg"></div>
            <div class="tm_invoice_info_list">
              <p class="tm_invoice_number tm_m0">N0 de facture: <b class="tm_primary_color">{{ $purchase->transaction_id }}</b></p>
              <p class="tm_invoice_date tm_m0">Date: <b class="tm_primary_color">{{ $purchase->created_at }}</b></p>
            </div>
          </div>
          <div class="tm_invoice_head tm_mb10">
            <div class="tm_invoice_left">
              <p class="tm_mb2"><b class="tm_primary_color">Facture à:</b></p>
              <p>
              {{ $user->username }} <br>
              {{ $user->address . ", " . $user->city }} <br>{{ $user->country }} <br>
                {{ $user->email }}
              </p>
            </div>
            <div class="tm_invoice_right tm_text_right">
              <p class="tm_mb2"><b class="tm_primary_color">Payer à:</b></p>
              <p>
                {{ $purchase->first_name . " " . $purchase->last_name }} <br>
                {{ $purchase->address . " " . $purchase->city }}<br>
                {{ $purchase->country }} <br>
                {{ $purchase->email }}
              </p>
            </div>
          </div>
          <div class="tm_table tm_style1 tm_mb30">
            <div class="tm_round_border">
              <div class="tm_table_responsive">
                <table>
                  <thead>
                    <tr>
                      <th class="tm_width_3 tm_semi_bold tm_primary_color tm_gray_bg">Article</th>
                      <th class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg">Prix</th>
                      <th class="tm_width_1 tm_semi_bold tm_primary_color tm_gray_bg">Quantité</th>
                      <th class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg tm_text_right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                  @foreach($purchase->purchaseCarts as $cart)
                    <tr>
                    <td class="tm_width_3">{{ $loop->index + 1 . ". " . $cart->cart->article->name }}</td>

                      <td class="tm_width_2">$ {{ $cart->cart->getActualPrice() }}</td>
                      <td class="tm_width_1">1</td>
                      <td class="tm_width_2 tm_text_right">$ {{ $cart->cart->getActualPrice() }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tm_invoice_footer">
              <div class="tm_left_footer">
                <p class="tm_mb2"><b class="tm_primary_color">Informations de paiement:</b></p>
                <p class="tm_m0">Mode de paiement : {{ $purchase->payment_method }} <br>Montant: $ {{ $purchase->amount }}</p>
              </div>
              <div class="tm_right_footer">
                <table>
                  <tbody>
                    <tr>
                      <td class="tm_width_3 tm_primary_color tm_border_none tm_bold">Sous-total</td>
                      <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_bold">$ {{ $purchase->amount }}</td>
                    </tr>
                    <tr>
                      <td class="tm_width_3 tm_primary_color tm_border_none tm_pt0">Taxe <span class="tm_ternary_color">(0%)</span></td>
                      <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_pt0">+ $ {{ $purchase->amount * 0 }}</td>
                    </tr>
                    <tr class="tm_border_top tm_border_bottom">
                      <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_primary_color">Total général	</td>
                      <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_primary_color tm_text_right">$ {{ $purchase->amount }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="tm_padd_15_20 tm_round_border">
            <p class="tm_mb5"><b class="tm_primary_color">Termes et conditions:</b></p>
            <ul class="tm_m0 tm_note_list">
              <li>Toutes les réclamations relatives à la quantité ou aux erreurs d'expédition seront annulées par l'acheteur à moins qu'elles ne soient faites par écrit au vendeur dans les trente (30) jours suivant la livraison des marchandises à l'adresse indiquée.</li>
              <li>Les dates de livraison ne sont pas garanties et le vendeur n'est pas responsable des dommages qui pourraient être encourus en raison de tout retard dans l'expédition des marchandises en vertu des présentes. Les taxes sont exclues sauf indication contraire.</li>
            </ul>
          </div><!-- .tm_note -->
        </div>
      </div>
       <div class="tm_invoice_btns tm_hide_print">
        <a href="javascript:window.print()" class="tm_invoice_btn tm_color2">
<!--           <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M384 368h24a40.12 40.12 0 0040-40V168a40.12 40.12 0 00-40-40H104a40.12 40.12 0 00-40 40v160a40.12 40.12 0 0040 40h24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><rect x="128" y="240" width="256" height="208" rx="24.32" ry="24.32" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="M384 128v-24a40.12 40.12 0 00-40-40H168a40.12 40.12 0 00-40 40v24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><circle cx="392" cy="184" r="24" fill='currentColor'/></svg>
          </span> -->
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M320 336h76c55 0 100-21.21 100-75.6s-53-73.47-96-75.6C391.11 99.74 329 48 256 48c-69 0-113.44 45.79-128 91.2-60 5.7-112 35.88-112 98.4S70 336 136 336h56M192 400.1l64 63.9 64-63.9M256 224v224.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
          </span>
          <span class="tm_btn_text">Télécharger</span>
        </a>
<!--         <button onclick="myfunc()" id="tm_download_btn" data-transaction_id="{{ $purchase->transaction_id }}" class="tm_invoice_btn tm_color1">
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M320 336h76c55 0 100-21.21 100-75.6s-53-73.47-96-75.6C391.11 99.74 329 48 256 48c-69 0-113.44 45.79-128 91.2-60 5.7-112 35.88-112 98.4S70 336 136 336h56M192 400.1l64 63.9 64-63.9M256 224v224.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
          </span>
          <span class="tm_btn_text">Download</span>
        </button> -->
      </div>
    </div>
  </div>
<!-- 
<script src="/jquery.min.js"></script>
<script src="/jspdf.min.js"></script> -->
  <!-- <script src="{{ asset('invoice_assets/js/html2canvas.min.js')}}"></script> -->

  <script>
    function myfunc() {
      console.log('lllll')
    var downloadSection = $('#tm_download_section');
    var cWidth = downloadSection.width();
    var cHeight = downloadSection.height();
    var topLeftMargin = 0;
    var pdfWidth = cWidth + topLeftMargin * 2;
    var pdfHeight = pdfWidth * 1.5 + topLeftMargin * 2;
    var canvasImageWidth = cWidth;
    var canvasImageHeight = cHeight;
    var totalPDFPages = Math.ceil(cHeight / pdfHeight) - 1;
    //var transactionId = $(this).data('transaction_id');

    html2canvas(downloadSection[0], { allowTaint: true }).then(function (
      canvas
    ) {
      canvas.getContext('2d');
      var imgData = canvas.toDataURL('image/png', 1.0);
      var pdf = new jsPDF('p', 'pt', [pdfWidth, pdfHeight]);
      pdf.addImage(
        imgData,
        'PNG',
        topLeftMargin,
        topLeftMargin,
        canvasImageWidth,
        canvasImageHeight
      );
      for (var i = 1; i <= totalPDFPages; i++) {
        pdf.addPage(pdfWidth, pdfHeight);
        pdf.addImage(
          imgData,
          'PNG',
          topLeftMargin,
          -(pdfHeight * i) + topLeftMargin * 0,
          canvasImageWidth,
          canvasImageHeight
        );
      }
      //pdf.save( transactionId + '.pdf');
      pdf.save('download.pdf');
    });
  }
  </script>
  <script src="{{ asset('invoice_assets/js/main.js')}}"></script>
</body>
</html>