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

        $transactionFee = $this->getTransactionFee($data);
        $viInterchange = $this->getVIInterchange($data);
        $mcInterchange = $this->getMCInterchange($data);

    }

    public function getTransactionFee($string) {
         preg_match_all('/.*\b(MASTERCARD|VISA|DISCOVER|DEBIT)\b.*(SALE).*/',$string,$match);
         return  collect($match[0])
                    ->map(function($line){
                        return preg_split('/(SALES|DISCOUNT |DISC |RATE TIMES|Service charges)/i',$line);
                    })
                    ->map(function($txFee){
                              $txFee = collect($txFee)->filter(function($item) {
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
