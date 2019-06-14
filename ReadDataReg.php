<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Psy\Util\Str;

class ReadDataReg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'read:regx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = Storage::get('sample.txt');
        // Q1
        $transactionFee = $this->getTransactionFee($data);

        //Q2
        $viInterchange = $this->getVIInterchange($data);
        $mcInterchange = $this->getMCInterchange($data);

        //Q3
        $summaryByBatch = $this->getDataTable("SUMMARY BY CARD TYPE",'/.*\d{12}.*/','/.*Batch.* Items/',$data);

        $summaryByCardType = $this->getDataTable("SUMMARY BY CARD TYPE",'/.*\b(Mastercard|VISA|Discover|AMEX ACQ|Debit)\b.*(\d{2})/','/.*Card Type.* Items/',$data);

        $summaryByDay = $this->getDataTable("SUMMARY BY DAY",'/\b.*(\d{2}\/\d{2}\/\d{2})\b.*(\d{2})/',[
            'Date Submitted','Submitted Amount', 'Chargebacks/Reversals','Adjustments','Fees','Amount Processed'
        ],$data);

    }

    public function getTransactionFee($string) {
         preg_match_all('/.*\b(MASTERCARD|VISA|DISCOVER|DEBIT)\b.*(SALE).*/',$string,$match);
         return  collect($match[0])
                    ->map(function($line){
                        return preg_split('/(SALES|DISCOUNT |DISC |RATE TIMES|Service charges)/i',$line);
                    })
                    ->map(function($txFee){
                        $txFee = collect($txFee)
                                ->filter(function($item) {
                                        return str_replace(' ','',$item) !== '';
                                 })->map(function($item){
                                     return trim($item);
                                 });
                            return $txFee;
                    })
                    ->map(function($txFee){
                         return [$txFee[0]=>[
                             "discRate"=>$txFee[2],
                             "amount"=>$txFee[4],
                        ]];
                 });
    }

    protected function getDataTable($tableName,$rowPattern,$headerPattern,$dataString) {
        preg_match_all($rowPattern,$dataString,$rows);
        if(is_array($headerPattern)) {
            $header = $headerPattern;
        }else{
            preg_match_all($headerPattern,$dataString,$header);
            $header = $header[0][0];
            $header = collect(explode(" ",$header))
                ->filter(function($item){
                    return str_replace(' ','',$item) !== '';
                })
                ->map(function($item){
                    return trim($item);
                });
        }


        return [
            "name" => $tableName,
            "header" => $header,
            "rows"=> collect($rows[0])
                    ->map(function($line){
                        return collect(explode("  ",$line))
                            ->filter(function($item){
                                return str_replace(' ','',$item) !== '';
                            })
                            ->map(function($item){
                                return trim($item);
                            });
                    })->filter(function($row) use($header){
                        return count($row) == count($header);
                    })

        ];
    }

    public function getVIInterchange($string) {
        preg_match_all('/.*(VI-)\b.*(\(DB|\(PP)\b.*/',$string,$match);
        return collect($match[0])->map(function($line){
            return preg_split('/(-\$)/i',$line)[1];
        })->sum();
    }
    public function getMCInterchange($string) {
        preg_match_all('/.*(MC-)\b.*(\(DB)\b.*/',$string,$match);
        return collect($match[0])->map(function($line){
            return preg_split('/(-\$)/i',$line)[1];
        })->sum();
    }
}
