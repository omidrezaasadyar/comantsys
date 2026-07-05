<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<style>

{!! $fontFaces !!}
  @page { margin: 8mm 7mm 8mm 7mm; }
  * { box-sizing: border-box; }
  body { font-family: vazirmatn; direction: rtl; color: #111; font-size: 11px; }

  table.head { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 6px; }  table.head td { vertical-align: middle; padding: 0 4px; }
  table.head td.cell { border: 1px solid #6f9fbd; border-radius: 10px; padding: 8px 10px; background: #fff; }

  .lbl { color: #5a6470; font-weight: bold; }
  .kv { font-size: 12px; font-weight: bold; }
  .kv .val { font-weight: bold; }

  .logoCell { text-align: center; }
  .logoCell img { width: 95px; height: 95px; object-fit: contain; }

  .centerTitle { text-align: center; }
  .centerTitle .l1 { font-size: 22px; font-weight: 800; }
  .centerTitle .l2 { font-size: 15px; font-weight: 700; color: #2f6f91; margin-top: 6px; }

/* src/resources/views/invoices/pdf/magan_fa.blade.php */
  .barcodeBox { min-height: 34px; height: 34px; text-align: center; color: #9aa7b3; font-size: 9px; display: flex; align-items: center; justify-content: center; }
  .sectionTitle { border: 1px solid #6f9fbd; border-radius: 10px; background: #c2dbef; padding: 6px 10px; text-align: center; font-weight: bold; font-size: 13px; margin: 9px 0 6px 0; }

  table.items { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #6f9fbd; border-radius: 10px; overflow: hidden; font-size: 11px; }
  table.items thead th { background: #2f6f91; color: #fff; padding: 7px 4px; text-align: center; font-weight: bold; }
  table.items tbody td { padding: 6px 4px; border-top: 1px solid #9fcae3; text-align: center; }
  table.items tbody tr:nth-child(odd) td { background: #eff8fe; }
  table.items tbody tr:nth-child(even) td { background: #d7edf9; }
  table.items tfoot td { padding: 7px 4px; border-top: 1px solid #9fcae3; text-align: center; font-weight: bold; background: #f6fbff; }
  .td-desc { text-align: right; padding-right: 8px; }
  .td-code { color: #8a6200; font-weight: bold; }
  .totalLabel { text-align: left; padding-left: 8px; }

  .totalBox { border: 1px solid #6f9fbd; border-radius: 10px; padding: 9px 12px; margin-top: 8px; }
  .totalBox .gline { font-size: 14px; font-weight: bold; text-align: left; }
  .totalBox .gline .lbl { float: right; }
  .totalBox .words { border-top: 1px solid #d0dbe5; margin-top: 8px; padding-top: 7px; font-size: 11px; font-weight: bold; }

  .notesTitle { border: 1px solid #6f9fbd; border-radius: 10px 10px 0 0; background: #c2dbef; padding: 6px 10px; text-align: center; font-weight: bold; font-size: 12px; margin-top: 9px; }
  .notesBody { border: 1px solid #6f9fbd; border-top: none; border-radius: 0 0 10px 10px; padding: 9px; min-height: 55px; font-size: 10px; line-height: 1.7; }

  table.footer { width: 100%; border-collapse: collapse; margin-top: 8px; }
  table.footer td { vertical-align: top; padding: 0 4px; }
  .footerHead { border: 1px solid #6f9fbd; border-radius: 10px 10px 0 0; background: #c2dbef; padding: 5px 8px; text-align: center; font-weight: bold; font-size: 12px; }
  .footerBody { border: 1px solid #6f9fbd; border-top: none; border-radius: 0 0 10px 10px; padding: 8px 10px; min-height: 130px; font-size: 10px; position: relative; }
  .footerBody .name { font-weight: bold; font-size: 12px; text-align: center; margin-bottom: 4px; }
  .footerBody .line { margin-bottom: 3px; font-weight: bold; }
  .stamp { position: absolute; left: 10px; bottom: 8px; width: 100px; height: 100px; object-fit: contain; }
  .sellerContact { font-size: 9px; color: #5f6b76; line-height: 1.9; margin-top: 6px; font-weight: bold; }
  .qrBox { border: 1px dashed #9fcae3; border-radius: 10px; min-height: 130px; text-align: center; color: #9aa7b3; font-size: 9px; padding: 8px; }

  .pdfFooter { text-align: center; font-size: 9px; color: #8a8f96; margin-top: 12px; font-weight: bold; }
  .watermark {
    position: fixed;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size: 95px;
    font-weight: bold;
    color: rgba(220, 0, 0, 0.08);
    z-index: 1000;
    white-space: nowrap;
    pointer-events: none;
  }
</style>
</head>
<body>
@php
  $fa = fn ($v) => \App\Support\NumberToWords::toPersianDigits(number_format((float) $v));
  $faStr = fn ($v) => \App\Support\NumberToWords::toPersianDigits((string) ($v ?? ''));
  $jdate = fn ($d) => $d ? $faStr(\Morilog\Jalali\Jalalian::fromDateTime($d)->format('Y/m/d')) : '';
  $isInvoice = $invoice->type === 'invoice';
@endphp

<div class="watermark">{{ $isInvoice ? 'فاکتور فروش' : 'پیش‌فاکتور' }}</div>

  {{-- سربرگ: لوگو / عنوان / تاریخ و شماره --}}
  <table class="head">
    <tr>
      <td class="cell logoCell" style="width: 175px; height: 110px;">
        @if($logoPath)
          <img src="{{ $logoPath }}" alt="logo">
        @endif
      </td>
      <td class="cell centerTitle" style="height: 110px;">
        <div class="l1">{{ $invoice->company?->name }}</div>
        <div class="l2">{{ $isInvoice ? 'فاکتور فروش محصولات و خدمات' : 'پیش‌فاکتور محصولات و خدمات' }}</div>
      </td>
      <td class="cell" style="width: 175px; height: 110px;">
        <div class="kv"><span class="lbl">تاریخ :</span> <span class="val">{{ $jdate($invoice->invoice_date) }}</span></div>
        <div style="height:8px"></div>
        <div class="kv"><span class="lbl">شماره :</span> <span class="val">{{ $faStr($invoice->invoice_number) }}</span></div>
      </td>
    </tr>
  </table>

  {{-- شناسه ملی / کد اقتصادی / جای بارکد --}}
  <table class="head">
    <tr>
      <td class="cell kv" style="width:33%"><span class="lbl">شناسه ملی :</span> {{ $faStr($invoice->company?->national_id) }}</td>
      <td class="cell kv" style="width:33%"><span class="lbl">کد اقتصادی :</span> {{ $faStr($invoice->company?->economic_code) }}</td>
      <td style="width:34%">
        <div class="barcodeBox">
          @if($barcodeData)
            <img src="{{ $barcodeData }}" style="max-width:100%; height:28px; object-fit:contain;" alt="barcode">
          @else
            بارکد شماره فاکتور
          @endif
        </div>
      </td>
    </tr>
  </table>

  {{-- استعلام / کارشناس / خریدار --}}
  <table class="head">
    <tr>
      <td class="cell kv"><span class="lbl">شماره استعلام :</span> {{ $faStr($invoice->inquiry_number) }}</td>
<td class="cell kv" style="width:1%; white-space:nowrap"><span class="lbl">تاریخ استعلام :</span> {{ $jdate($invoice->inquiry_date) }}</td>      <td class="cell kv" style="width:32%"><span class="lbl">کارشناس :</span> {{ $invoice->expert_name }}</td>
      <td class="cell kv centerTitle">{{ $invoice->customer?->name }}</td>
    </tr>
  </table>

  <div class="sectionTitle">کالا و خدمات</div>

  {{-- جدول اقلام --}}
  @php
    $sumNet = 0; $sumVat = 0; $sumTotal = 0;
    $rate = (float) $invoice->vat_rate;
    $isIRR = $invoice->currency === 'IRR';
    $curLabel = $isIRR ? 'ریال' : $invoice->currency;
  @endphp

  <table class="items">
    <thead>
      <tr>
        <th style="width:28px">ردیف</th>
        <th style="width:65px">کد کالا</th>
        <th>شرح کالا و خدمات</th>
        <th style="width:38px">تعداد</th>
        <th style="width:85px">قیمت واحد</th>
        <th style="width:95px">جمع فروش</th>
        <th style="width:85px">مالیات و عوارض</th>
        <th style="width:105px">مبلغ کل</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $i => $item)
        @php
          $net = (float) $item->net_sales;
          $vat = $isIRR ? round($net * $rate / 100) : 0;
          $total = $net + $vat;
          $sumNet += $net; $sumVat += $vat; $sumTotal += $total;
        @endphp
        <tr>
          <td>{{ $fa($i + 1) }}</td>
          <td class="td-code">{{ $faStr($item->item_code) }}</td>
          <td class="td-desc">{{ $item->description }}</td>
          <td>{{ $fa((float) $item->quantity) }}</td>
          <td>{{ $fa((float) $item->unit_price) }}</td>
          <td>{{ $fa($net) }}</td>
          <td>{{ $fa($vat) }}</td>
          <td>{{ $fa($total) }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="totalLabel">جمع ستون</td>
        <td>{{ $fa($sumNet) }}</td>
        <td>{{ $fa($sumVat) }}</td>
        <td>{{ $fa($sumTotal) }}</td>
      </tr>
    </tfoot>
  </table>

  {{-- مبلغ کل (عدد سمت چپ) + به حروف --}}
  <div class="totalBox">
    <div class="gline"><span class="lbl">مبلغ کل ({{ $curLabel }}) :</span> {{ $fa((float) $invoice->grand_total) }}</div>
    @if($totalInWords)
      <div class="words">به حروف : {{ $totalInWords }}</div>
    @endif
  </div>

  {{-- توضیحات --}}
  <div class="notesTitle">توضیحات</div>
  <div class="notesBody">{!! nl2br(e($invoice->notes)) !!}</div>

  {{-- فوتر: فروشنده / خریدار / QR --}}
  <table class="footer">
    <tr>
      <td style="width:40%">
        <div class="footerHead">فروشنده</div>
        <div class="footerBody">
          <div class="name">{{ $invoice->company?->name }}</div>
          @if($stampPath)
            <img class="stamp" src="{{ $stampPath }}" alt="stamp">
          @endif
          <div class="sellerContact">
            @if($invoice->company?->phone)<div>تلفن ثابت: {{ $faStr($invoice->company->phone) }}</div>@endif
            @if($invoice->company?->mobile)<div>موبایل: {{ $faStr($invoice->company->mobile) }}</div>@endif
            @if($invoice->company?->messenger_phone)<div>پیام‌رسان‌ها: {{ $faStr($invoice->company->messenger_phone) }}</div>@endif
            @if($invoice->company?->email)<div>ایمیل: {{ $invoice->company->email }}</div>@endif
          </div>
        </div>
      </td>
      <td style="width:38%">
        <div class="footerHead">خریدار</div>
        <div class="footerBody">
          <div class="line">نام/شرکت: {{ $invoice->customer?->name }}</div>
          <div class="line">شناسه ملی: {{ $faStr($invoice->customer?->national_id) }}</div>
          <div class="line">کد اقتصادی: {{ $faStr($invoice->customer?->economic_code) }}</div>
          <div class="line">تلفن: {{ $faStr($invoice->customer?->phone) }}</div>
          <div class="line">آدرس: {{ $invoice->customer?->address }}</div>
        </div>
      </td>
      <td style="width:22%">
        <div class="qrBox">
          @if($qrData)
            <img src="{{ $qrData }}" style="width:110px; height:110px; object-fit:contain;" alt="qr">
          @else
            QR کد تأیید
          @endif
        </div>
      </td>
    </tr>
  </table>

  <div class="pdfFooter">{{ $invoice->company?->footer_note }}</div>

</body>
</html>
