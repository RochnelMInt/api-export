<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
      @font-face {
  font-family: SourceSansPro;
  src: url(SourceSansPro-Regular.ttf);
}

.clearfix {
  width: 80%;
  content: "";
  display: table;
  clear: both;
}

a {
  color: #0087C3;
  text-decoration: none;
}

body {
  width: 80%;
  position: relative;
  width: 21cm;  
  height: 29.7cm; 
  margin: 0 auto; 
  color: #555555;
  background: #FFFFFF; 
  font-family: Arial, sans-serif; 
  font-size: 14px; 
  font-family: SourceSansPro;
}

header {
  padding: 10px 0;
  margin-bottom: 20px;
  border-bottom: 1px solid #AAAAAA;
}

#logo {
  float: left;
  margin-top: 8px;
}

#logo img {
  height: 70px;
}

#company {
  float: right;
  text-align: right;
}


#details {
  margin-bottom: 100px;
  margin-top: 50px;
}

#client {
  padding-left: 6px;
  border-left: 6px solid #0087C3;
  float: left;
}

#client .to {
  color: #777777;
}

h2.name {
  font-size: 1.0em;
  font-weight: normal;
  margin: 0;
}

#invoice {
  float: right;
  text-align: right;
}

#invoice h3 {
  color: #0087C3;
  line-height: 1em;
  font-weight: normal;
  margin: 0  0 10px 0;
}

#invoice .date {
  font-size: 1.0em;
  color: #777777;
}

table {
  width: 80%;
  border-collapse: collapse;
  border-spacing: 0;
  margin-bottom: 20px;
}

table th,
table td {
  padding: 10px;
  background: #EEEEEE;
  text-align: center;
  border-bottom: 1px solid #FFFFFF;
}

table th {
  white-space: nowrap;        
  font-weight: normal;
}

table td {
  text-align: right;
}

table td h3{
  color: #DAA202;
  font-size: 1.0em;
  font-weight: normal;
  margin: 0 0 0.2em 0;
}

table .no {
  color: #FFFFFF;
  font-size: 0.8em;
  background: #DAA202;
}

table .desc {
  text-align: left;
}

table .unit {
  background: #DDDDDD;
}

table .qty {
}

table .total {
  background: #DAA202;
  color: #FFFFFF;
}

table td.unit,
table td.qty,
table td.total {
  font-size: 0.8em;
}

table tbody tr:last-child td {
  border: none;
}

table tfoot td {
  padding: 5px 10px;
  background: #FFFFFF;
  border-bottom: none;
  font-size: 1.0em;
  white-space: nowrap; 
  border-top: 1px solid #AAAAAA; 
}

table tfoot tr:first-child td {
  border-top: none; 
}

table tfoot tr:last-child td {
  color: #DAA202;
  font-size: 1.0em;
  border-top: 1px solid #DAA202; 

}

table tfoot tr td:first-child {
  border: none;
}

#thanks{
  font-size: 1.2em;
  margin-bottom: 50px;
}

#notices{
  padding-left: 6px;
  border-left: 6px solid #0087C3;  
}

#notices .notice {
  font-size: 1.0em;
}

footer {
  color: #777777;
  width: 80%;
  height: 30px;
  position: absolute;
  bottom: 0;
  border-top: 1px solid #AAAAAA;
  padding: 8px 0;
  text-align: center;
}


    </style>
  </head>
  <body>
    <header class="clearfix" style="margin-top:10px">
      <h2 style="text-align: center; color: #DAA202 " class="name">DODO SHOP INVOICE</h2>
      <div id="company" style="margin-top:15px">
        
        <div>{{ $user->username }}</div>
        <div>{{ $user->address }}</div>
        <div><a href="">{{ $user->email }}</a></div>
      </div>
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">INVOICE TO :</div>
          <h2 class="name">{{ $purchase->first_name . " " . $purchase->last_name }}</h2>
          <div class="address">{{ $purchase->address . " " . $purchase->city . " " .  $purchase->country}}</div>
          <div class="email"><a href="mailto:john@example.com">{{ $purchase->email }}</a></div>
        </div>
        <div id="invoice">
          <h3>{{ $purchase->transaction_id }}</h3>
          <div class="date">Date of Invoice: {{ $purchase->created_at }}</div>
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0">
        <thead>
          <tr>
            <th class="no">#</th>
            <th class="desc">DESCRIPTION</th>
            <th class="unit">UNIT PRICE</th>
            <th class="qty">QUANTITY</th>
            <th class="total">TOTAL</th>
          </tr>
        </thead>
        <tbody>
          @foreach($purchase->purchaseCarts as $cart)
          <tr>
            <td class="no">{{ $loop->index }}</td>
            <td class="desc">
              <h3>{{ $cart->cart->variant->article->name }}</h3>
              <div>
                @foreach($cart->cart->variant->featureValues as $itm)
                  <span style="font-size: 0.8em">{{ $itm->feature->name . " : " . $itm->value }}</span><br />
                @endforeach
              </div>
            </td>
            <td class="unit">${{ $cart->cart->amount * 0.81 }}</td>
            <td class="qty">{{ $cart->cart->quantity }}</td>
            <td class="total">${{ $cart->cart->quantity * $cart->cart->amount * 0.81 }}</td>
          </tr>
          @endforeach

        </tbody>
        <tfoot>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">SUBTOTAL</td>
            <td>${{ $purchase->amount * 0.81 }}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">TAX 19%</td>
            <td>${{ $purchase->amount * 0.19 }}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">GRAND TOTAL</td>
            <td>${{ $purchase->amount }}</td>
          </tr>
        </tfoot>
      </table>
      <div id="thanks">Thank you!</div>
      <div id="notices">
        <div>TRANSACTION ID : </div>
        <div class="notice">{{ $purchase->transaction_id }}</div>
      </div>
    </main>
    <footer>
      Invoice was created on a computer and is valid without the signature and seal.
    </footer>
  </body>
</html>