<?php
if(!class_exists('EOD_API'))
{
    class EOD_API{
        public function __construct(){

        }

        /**
         * Get User API key (token)
         * @return string
         */
        public static function get_eod_api_key()
        {
            $plugin_options = get_option('eod_options');
            if($plugin_options === false) $plugin_options = array();
            //Default token
            $apiKey = EOD_DEFAULT_API;
            if(array_key_exists('api_key', $plugin_options) && $plugin_options['api_key']){
                $apiKey = $plugin_options['api_key'];
            }
            return($apiKey);
        }

        /**
         * Check the API key (token) and its tariff plan for the possibility of receiving data
         * @param string type
         * @param array props
         * @return mixed
         */
        public static function check_token_capability($type, $props)
        {
            if(($type === 'historical' || $type === 'live') && isset($props['target'])){
                return self::get_real_time_ticker($type, $props['target']);
            }

            if($type === 'news' && ($props['target'] || $props['tag'])){
                return self::get_news($props['target'], array(
                    'limit' => 1,
                    'tag'   => ''
                ));
            }

            if($type === 'fundamental' && $props['target']){
                return self::get_fundamental_data($props['target']);
            }

            return array();
        }


        /**
         * Check the API key (token) and its tariff plan for the possibility of receiving data
         * @param string target
         * @param array args
         * @return mixed
         */
        public static function get_news( $target, $args = array() ){
            // Check target/tag
            if((!isset($target) || gettype($target) !== 'string') && (!isset($args['tag']) || gettype($args['tag']) !== 'string'))
                return array('error' => 'wrong target');

            $apiKey = self::get_eod_api_key();

            // Base URL
            $apiUrl = "https://eodhistoricaldata.com/api/news?api_token=$apiKey";

            // Target
            if($target && gettype($target) === 'string')
                $apiUrl .= "&s=$target";
            // Tag
            if($args['tag'] && gettype($args['tag']) === 'string')
                $apiUrl .= "&t=".$args['tag'];

            // Offset
            $offset = isset($args['offset']) ? intval($args['offset']) : 0;
            if($offset < 0) $offset = 0;
            $apiUrl .= "&offset=$offset";

            // Limit
            $limit = isset($args['limit']) ? intval($args['limit']) : 50;
            if($limit < 1) $limit = 1;
            if($limit > 1000) $limit = 1000;
            $apiUrl .= "&limit=$limit";

            // Date range
            if($args['from']){
                $d = DateTime::createFromFormat('Y-m-d', $args['from']);
                if($d && $d->format('Y-m-d') === $args['from'])
                    $apiUrl .= "&from=".$args['from'];
            }
            if($args['to']){
                $d = DateTime::createFromFormat('Y-m-d', $args['to']);
                if($d && $d->format('Y-m-d') === $args['to'])
                    $apiUrl .= "&to=".$args['to'];
            }

            return self::call_eod_api($apiUrl);
        }


        /**
         * Searching for items from API by string
         * @param string $needle
         * @return mixed
         */
        public static function search_by_string($needle)
        {
            if(!$needle){
                return array('error' => 'empty string');
            }

            $apiKey = self::get_eod_api_key();
            $apiUrl = "https://eodhistoricaldata.com/api/search/".
                $needle .
                "?api_token=$apiKey";

            $data = self::call_eod_api($apiUrl);
            if(!$data)
                return array('error' => 'no result from api');

            return $data;
        }

        /**
         * Get Fundamental Data
         * @param string target
         * @return mixed
         */
        public static function get_fundamental_data($target)
        {
            if(!is_string($target)) return array('error' => 'Wrong target');

            $apiKey = self::get_eod_api_key();
            $apiUrl = "https://eodhistoricaldata.com/api/fundamentals/".
                strtoupper($target).
                "?api_token=$apiKey".
                "&fmt=json";

            $fundamental_data = self::call_eod_api($apiUrl);
            if(!$fundamental_data)
                return array('error' => 'no result from fundamental data api');

            return $fundamental_data;
        }


        /**
         * Get Ticker infos and calculate evolution
         * @param string type
         * @param mixed targets
         * @return mixed
         */
        public static function get_real_time_ticker($type = 'historical', $targets)
        {
            $apiKey = self::get_eod_api_key();

            if($type === 'historical'){
                if(!is_array($targets)){
                    $targets = array($targets);
                }
                $apiUrl = "https://eodhistoricaldata.com/api/eod-bulk-last-day/US".
                    "?api_token=$apiKey".
                    "&fmt=json".
                    "&symbols=".strtoupper(implode(',', $targets));
            }else if($type === 'live'){
                if(!is_array($targets)){
                    $targets = array($targets[0]);
                }
                $extraTargets = strtoupper(implode(',', array_slice($targets,1)));
                $apiUrl = "https://eodhistoricaldata.com/api/real-time/".strtoupper($targets[0]).
                    "?api_token=$apiKey".
                    "&fmt=json";
                //Extra target management.
                if($extraTargets) $apiUrl .= "&s=$extraTargets";
            }else{
                return array('error' => 'wrong type');
            }

            $tickerData = self::call_eod_api($apiUrl);
            if(!$tickerData)
                return array('error' => 'no result from real time api');

            return $tickerData;
        }


        /**
         * Will cal api asking then returns the result
         * @param string apiUrl
         * @return mixed
         */
        public static function call_eod_api($apiUrl)
        {
            if(!$apiUrl || gettype($apiUrl) !== 'string')
                return array('error' => 'Wrong API URL');

            //Create request and get result
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $response = curl_exec($ch);

            //Parse response (headers vs body)
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($ch);

            //Parse json body or return error
            if(!$body || strlen(trim($body)) === 0){
                return array('error_code' => 'null', 'error' => 'null body', 'headers'  => $headers);
            }
            if(in_array($body, ["Forbidden. Please contact support@eodhistoricaldata.com", "Forbidden"])){
                return array('error_code' => 'forbidden', 'error' => 'Forbidden. Perhaps your data plan does not allow you to receive data. Please contact support@eodhistoricaldata.com', 'headers'  => $headers);
            }
            if(in_array($body, ["Unauthenticated"])){
                return array('error_code' => 'unauthenticated', 'error' => 'Unauthenticated', 'headers'  => $headers);
            }

            try {
                $result = json_decode($body, true);
            } catch (Exception $err) {
                $result = array('error' => $body, 'exception' => $err->getMessage(), 'headers'  => $headers);
                error_log('Error getting api result : '.print_r($err,true));
            }

            return $result;
        }


        /**
         * Get list of news topics
         * @return array
         */
        public function get_news_topics()
        {
            return ['balance sheet', 'capital employed', 'class action', 'company announcement', 'consensus eps estimate', 'consensus estimate', 'credit rating', 'discounted cash flow', 'dividend payments', 'earnings estimate', 'earnings growth', 'earnings per share', 'earnings release', 'earnings report', 'earnings results', 'earnings surprise', 'estimate revisions', 'european regulatory news', 'financial results', 'fourth quarter', 'free cash flow', 'future cash flows', 'growth rate', 'initial public offering', 'insider ownership', 'insider transactions', 'institutional investors', 'institutional ownership', 'intrinsic value', 'market research reports', 'net income', 'operating income', 'present value', 'press releases', 'price target', 'quarterly earnings', 'quarterly results', 'ratings', 'research analysis and reports', 'return on equity', 'revenue estimates', 'revenue growth', 'roce', 'roe', 'share price', 'shareholder rights', 'shareholder', 'shares outstanding', 'strong buy', 'total revenue', 'zacks investment research', 'zacks rank'];
        }


        /**
         * Get array with hierarchy for fundamental data
         * @param string $type
         * @return array
         */
        public function get_fd_hierarchy()
        {
            return array(
                "index" => array(
                    "General" => array(
                        "Code","Type","Name","Exchange","CurrencyCode","CurrencyName","CurrencySymbol","CountryName","CountryISO"
                    ),
                    "Components" => "Components",
                    "HistoricalTickerComponents" => "Historical Ticker Components",
                ),
                "fund" => array(
                    "General" => array(
                        "Code","Type","Name","Exchange","CurrencyCode","CurrencyName","CurrencySymbol","CountryName",
                        "CountryISO","ISIN","CUSIP","Fund_Summary","Fund_Family","Fund_Category","Fund_Style",
                        "Fiscal_Year_End","MarketCapitalization"
                    ),
                    "MutualFund_Data" => array(
                        "Fund_Category","Fund_Style","Nav","Prev_Close_Price","Update_Date","Portfolio_Net_Assets",
                        "Share_Class_Net_Assets","Morning_Star_Rating","Morning_Star_Risk_Rating","Morning_Star_Category",
                        "Inception_Date","Currency","Domicile","Yield","Yield_YTD","Yield_1Year_YTD","Yield_3Year_YTD",
                        "Yield_5Year_YTD","Expense_Ratio","Expense_Ratio_Date","Asset_Allocation","Value_Growth",
                        "Top_Holdings","Market_Capitalization","Top_Countries",
                        "Sector_Weights" => array("Cyclical","Defensive","Sensitive"),
                        "World_Regions" => array("Americas","Greater Asia","Greater Europe","Market Classification"),
                    )
                ),
                "etf" => array(
                    "General" => array(
                        "Code","Type","Name","Exchange","CurrencyCode","CurrencyName","CurrencySymbol","CountryName",
                        "CountryISO","Description","Category","UpdatedAt"
                    ),
                    "Technicals" => array(
                        "Beta","52WeekHigh","52WeekLow","50DayMA","200DayMA"
                    ),
                    "ETF_Data" => array(
                        "ISIN","Company_Name","Company_URL","ETF_URL","Domicile","Index_Name","Yield",
                        "Dividend_Paying_Frequency","Inception_Date","Max_Annual_Mgmt_Charge","Ongoing_Charge",
                        "Date_Ongoing_Charge","NetExpenseRatio","AnnualHoldingsTurnover","TotalAssets","Average_Mkt_Cap_Mil",
                        "Market_Capitalisation" => array("Mega","Big","Medium","Small","Micro"),
                        "Asset_Allocation","World_Regions","Sector_Weights","Fixed_Income","Holdings_Count",
                        "Top_10_Holdings","Holdings","MorningStar",
                        "Valuations_Growth" => array(
                            "Valuations_Rates_Portfolio","Valuations_Rates_To_Category","Growth_Rates_Portfolio","Growth_Rates_To_Category"
                        ),
                        "Performance" => array(
                            "1y_Volatility","3y_Volatility","3y_ExpReturn","3y_SharpRatio","Returns_YTD",
                            "Returns_1Y","Returns_3Y","Returns_5Y","Returns_10Y"
                        )
                    ),

                ),
                "common_stock" => array(
                    "General" => array(
                        "Code","Type","Name","Exchange","CurrencyCode","CurrencyName","CurrencySymbol","CountryName",
                        "CountryISO","ISIN","CUSIP","CIK","EmployerIdNumber","FiscalYearEnd","IPODate",
                        "InternationalDomestic","Sector","Industry","GicSector","GicGroup","GicIndustry","GicSubIndustry",
                        "HomeCategory","IsDelisted","Description","Address",
                        "AddressData" => array("Street","City","State","Country","ZIP"),
                        "Listings","Officers","Phone","WebURL","LogoURL","FullTimeEmployees","UpdatedAt"
                    ),
                    "Highlights" => array(
                        "MarketCapitalization","MarketCapitalizationMln","EBITDA","PERatio","PEGRatio",
                        "WallStreetTargetPrice","BookValue","DividendShare","DividendYield","EarningsShare",
                        "EPSEstimateCurrentYear","EPSEstimateNextYear","EPSEstimateNextQuarter","EPSEstimateCurrentQuarter",
                        "MostRecentQuarter","ProfitMargin","OperatingMarginTTM","ReturnOnAssetsTTM","ReturnOnEquityTTM",
                        "RevenueTTM","RevenuePerShareTTM","QuarterlyRevenueGrowthYOY","GrossProfitTTM","DilutedEpsTTM",
                        "QuarterlyEarningsGrowthYOY"
                    ),
                    "Valuation" => array(
                        "TrailingPE","ForwardPE","PriceSalesTTM","PriceBookMRQ","EnterpriseValue",
                        "EnterpriseValueRevenue","EnterpriseValueEbitda"
                    ),
                    "SharesStats" => array(
                        "SharesOutstanding","SharesFloat","PercentInsiders","PercentInstitutions","SharesShort",
                        "SharesShortPriorMonth","ShortRatio","ShortPercentOutstanding","ShortPercentFloat"
                    ),
                    "Technicals" => array(
                        "Beta","52WeekHigh","52WeekLow","50DayMA","200DayMA","SharesShort","SharesShortPriorMonth",
                        "ShortRatio","ShortPercent"
                    ),
                    "SplitsDividends" => array(
                        "ForwardAnnualDividendRate","ForwardAnnualDividendYield","PayoutRatio","DividendDate",
                        "ExDividendDate","LastSplitFactor","LastSplitDate","NumberDividendsByYear"
                    ),
                    "AnalystRatings" => array("Rating","TargetPrice","StrongBuy","Buy","Hold","Sell","StrongSell"),
                    "Holders" => array("Institutions","Funds"),
                    "InsiderTransactions",
                    "ESGScores" => array(
                        "Disclaimer","RatingDate","TotalEsg","TotalEsgPercentile","EnvironmentScore","EnvironmentScorePercentile",
                        "SocialScorePercentile","GovernanceScore","GovernanceScorePercentile","ControversyLevel","ActivitiesInvolvement"
                    ),
                )
            );
        }

        /**
         * Get array of fundamental data titles
         * @return array
         */
        public function get_fd_titles()
        {
            return array(
                "General" => "General",
                "General->Code" => "Code",
                "General->Type" => "Type",
                "General->Name" => "Name",
                "General->Exchange" => "Exchange",
                "General->CurrencyCode" => "Currency Code",
                "General->CurrencyName" => "Currency Name",
                "General->CurrencySymbol" => "Currency Symbol",
                "General->CountryName" => "Country Name",
                "General->CountryISO" => "Country ISO",
                "General->ISIN" => "ISIN",
                "General->CUSIP" => "CUSIP",
                "General->CIK" => "CIK",
                "General->EmployerIdNumber" => "Employer Id Number",
                "General->FiscalYearEnd" => "Fiscal Year End",
                "General->IPODate" => "IPO Date",
                "General->InternationalDomestic" => "International Domestic",
                "General->Sector" => "Sector",
                "General->Industry" => "Industry",
                "General->GicSector" => "Gic Sector",
                "General->GicGroup" => "Gic Group",
                "General->GicIndustry" => "Gic Industry",
                "General->GicSubIndustry" => "Gic Sub Industry",
                "General->HomeCategory" => "Home Category",
                "General->IsDelisted" => "Is Delisted",
                "General->Description" => "Description",
                "General->Address" => "Address",
                "General->AddressData" => "Address Data",
                "General->AddressData->Street" => "Street",
                "General->AddressData->City" => "City",
                "General->AddressData->State" => "State",
                "General->AddressData->Country" => "Country",
                "General->AddressData->ZIP" => "ZIP",
                "General->Listings" => "Listings",
                "General->Officers" => "Officers",
                "General->Phone" => "Phone",
                "General->WebURL" => "WebURL",
                "General->LogoURL" => "LogoURL",
                "General->Fund_Summary" => "Fund Summary",
                "General->Fund_Family" => "Fund Family",
                "General->Fund_Category" => "Fund Category",
                "General->Fund_Style" => "Fund Style",
                "General->Fiscal_Year_End" => "Fiscal Year End",
                "General->MarketCapitalization" => "Market Capitalization",
                "General->FullTimeEmployees" => "Full Time Employees",
                "General->Category" => "Category",
                "General->UpdatedAt" => "Updated At",
                "ETF_Data" => "ETF Data",
                "ETF_Data->ISIN" => "ISIN",
                "ETF_Data->Company_Name" => "Company Name",
                "ETF_Data->Company_URL" => "Company URL",
                "ETF_Data->ETF_URL" => "ETF URL",
                "ETF_Data->Domicile" => "Domicile",
                "ETF_Data->Index_Name" => "Index Name",
                "ETF_Data->Yield" => "Yield",
                "ETF_Data->Dividend_Paying_Frequency" => "Dividend Paying Frequency",
                "ETF_Data->Inception_Date" => "Inception Date",
                "ETF_Data->Max_Annual_Mgmt_Charge" => "Max Annual Mgmt Charge",
                "ETF_Data->Ongoing_Charge" => "Ongoing Charge",
                "ETF_Data->Date_Ongoing_Charge" => "Date Ongoing Charge",
                "ETF_Data->NetExpenseRatio" => "Net Expense Ratio",
                "ETF_Data->AnnualHoldingsTurnover" => "Annual Holdings Turnover",
                "ETF_Data->TotalAssets" => "TotalAssets",
                "ETF_Data->Average_Mkt_Cap_Mil" => "Average Mkt Cap Mil",
                "ETF_Data->Market_Capitalisation" => "Market Capitalisation",
                "ETF_Data->Market_Capitalisation->Mega" => "Mega",
                "ETF_Data->Market_Capitalisation->Big" => "Big",
                "ETF_Data->Market_Capitalisation->Medium" => "Medium",
                "ETF_Data->Market_Capitalisation->Small" => "Small",
                "ETF_Data->Market_Capitalisation->Micro" => "Micro",
                "ETF_Data->Asset_Allocation" => "Asset Allocation",
                "ETF_Data->World_Regions" => "World Regions",
                "ETF_Data->Sector_Weights" => "Sector Weights",
                "ETF_Data->Fixed_Income" => "Fixed Income",
                "ETF_Data->Holdings_Count" => "Holdings Count",
                "ETF_Data->Top_10_Holdings" => "Top 10 Holdings",
                "ETF_Data->Holdings" => "Holdings",
                "ETF_Data->MorningStar" => "Morning Star",
                "ETF_Data->Valuations_Growth" => "Valuations Growth",
                "ETF_Data->Valuations_Growth->Valuations_Rates_Portfolio" => "Valuations Rates Portfolio",
                "ETF_Data->Valuations_Growth->Valuations_Rates_To_Category" => "Valuations Rates To Category",
                "ETF_Data->Valuations_Growth->Growth_Rates_Portfolio" => "Growth Rates Portfolio",
                "ETF_Data->Valuations_Growth->Growth_Rates_To_Category" => "Growth Rates To Category",
                "ETF_Data->Performance" => "Performance",
                "ETF_Data->Performance->1y_Volatility" => "1y Volatility",
                "ETF_Data->Performance->3y_Volatility" => "3y Volatility",
                "ETF_Data->Performance->3y_ExpReturn" => "3y ExpReturn",
                "ETF_Data->Performance->3y_SharpRatio" => "3y SharpRatio",
                "ETF_Data->Performance->Returns_YTD" => "Returns YTD",
                "ETF_Data->Performance->Returns_1Y" => "Returns 1Y",
                "ETF_Data->Performance->Returns_3Y" => "Returns 3Y",
                "ETF_Data->Performance->Returns_5Y" => "Returns 5Y",
                "ETF_Data->Performance->Returns_10Y" => "Returns 10Y",
                "Highlights" => "Highlights",
                "Highlights->MarketCapitalization" => "Market Capitalization",
                "Highlights->MarketCapitalizationMln" => "Market Capitalization Mln",
                "Highlights->EBITDA" => "EBITDA",
                "Highlights->PERatio" => "PE Ratio",
                "Highlights->PEGRatio" => "PEG Ratio",
                "Highlights->WallStreetTargetPrice" => "Wall Street Target Price",
                "Highlights->BookValue" => "Book Value",
                "Highlights->DividendShare" => "Dividend Share",
                "Highlights->DividendYield" => "Dividend Yield",
                "Highlights->EarningsShare" => "Earnings Share",
                "Highlights->EPSEstimateCurrentYear" => "EPS Estimate Current Year",
                "Highlights->EPSEstimateNextYear" => "EPS Estimate Next Year",
                "Highlights->EPSEstimateNextQuarter" => "EPS Estimate Next Quarter",
                "Highlights->EPSEstimateCurrentQuarter" => "EPS Estimate Current Quarter",
                "Highlights->MostRecentQuarter" => "Most Recent Quarter",
                "Highlights->ProfitMargin" => "Profit Margin",
                "Highlights->OperatingMarginTTM" => "Operating Margin TTM",
                "Highlights->ReturnOnAssetsTTM" => "Return On Assets TTM",
                "Highlights->ReturnOnEquityTTM" => "Return On Equity TTM",
                "Highlights->RevenueTTM" => "RevenueTTM",
                "Highlights->RevenuePerShareTTM" => "Revenue Per Share TTM",
                "Highlights->QuarterlyRevenueGrowthYOY" => "Quarterly Revenue Growth YOY",
                "Highlights->GrossProfitTTM" => "Gross Profit TTM",
                "Highlights->DilutedEpsTTM" => "Diluted Eps TTM",
                "Highlights->QuarterlyEarningsGrowthYOY" => "Quarterly Earnings Growth YOY",
                "Valuation" => "Valuation",
                "Valuation->TrailingPE" => "Trailing PE",
                "Valuation->ForwardPE" => "Forward PE",
                "Valuation->PriceSalesTTM" => "Price Sales TTM",
                "Valuation->PriceBookMRQ" => "Price Book MRQ",
                "Valuation->EnterpriseValue" => "Enterprise Value",
                "Valuation->EnterpriseValueRevenue" => "Enterprise Value Revenue",
                "Valuation->EnterpriseValueEbitda" => "Enterprise ValueEbitda",
                "SharesStats" => "Shares Stats",
                "SharesStats->SharesOutstanding" => "Shares Outstanding",
                "SharesStats->SharesFloat" => "Shares Float",
                "SharesStats->PercentInsiders" => "Percent Insiders",
                "SharesStats->PercentInstitutions" => "Percent Institutions",
                "SharesStats->SharesShort" => "Shares Short",
                "SharesStats->SharesShortPriorMonth" => "Shares Short Prior Month",
                "SharesStats->ShortRatio" => "Short Ratio",
                "SharesStats->ShortPercentOutstanding" => "Short Percent Outstanding",
                "SharesStats->ShortPercentFloat" => "Short Percent Float",
                "Technicals" => "Technicals",
                "Technicals->Beta" => "Beta",
                "Technicals->52WeekHigh" => "52 Week High",
                "Technicals->52WeekLow" => "52 Week Low",
                "Technicals->50DayMA" => "50 Day MA",
                "Technicals->200DayMA" => "200 Day MA",
                "Technicals->SharesShort" => "Shares Short",
                "Technicals->SharesShortPriorMonth" => "Shares Short Prior Month",
                "Technicals->ShortRatio" => "Short Ratio",
                "Technicals->ShortPercent" => "Short Percent",
                "SplitsDividends" => "SplitsDividends",
                "SplitsDividends->ForwardAnnualDividendRate" => "Forward Annual Dividend Rate",
                "SplitsDividends->ForwardAnnualDividendYield" => "Forward Annual Dividend Yield",
                "SplitsDividends->PayoutRatio" => "Payout Ratio",
                "SplitsDividends->DividendDate" => "Dividend Date",
                "SplitsDividends->ExDividendDate" => "Ex Dividend Date",
                "SplitsDividends->LastSplitFactor" => "Last Split Factor",
                "SplitsDividends->LastSplitDate" => "Last Split Date",
                "SplitsDividends->NumberDividendsByYear" => "Number Dividends By Year",
                "AnalystRatings" => "AnalystRatings",
                "AnalystRatings->Rating" => "Rating",
                "AnalystRatings->TargetPrice" => "Target Price",
                "AnalystRatings->StrongBuy" => "Strong Buy",
                "AnalystRatings->Buy" => "Buy",
                "AnalystRatings->Hold" => "Hold",
                "AnalystRatings->Sell" => "Sell",
                "AnalystRatings->StrongSell" => "StrongSell",
                "Holders" => "Holders",
                "Holders->Institutions" => "Institutions",
                "Holders->Funds" => "Funds",
                "InsiderTransactions" => "InsiderTransactions",
                "ESGScores" => "ESG Scores",
                "ESGScores->Disclaimer" => "Disclaimer",
                "ESGScores->RatingDate" => "Rating Date",
                "ESGScores->TotalEsg" => "Total ESG",
                "ESGScores->TotalEsgPercentile" => "Total ESG Percentile",
                "ESGScores->EnvironmentScore" => "Environment Score",
                "ESGScores->EnvironmentScorePercentile" => "Environment Score Percentile",
                "ESGScores->SocialScorePercentile" => "Social Score Percentile",
                "ESGScores->GovernanceScore" => "Governance Score",
                "ESGScores->GovernanceScorePercentile" => "Governance Score Percentile",
                "ESGScores->ControversyLevel" => "Controversy Level",
                "ESGScores->ActivitiesInvolvement" => "Activities Involvement",
            );
        }

        /**
         * Get library of financials labels
         * @return array
         */
        public function get_financials_lib()
        {
            return array(
                "Earnings->History" => array(
                    "Earnings" => array(
                        "History" => array(
                            "_timeline_History" => "quarterly",
                            "beforeAfterMarket" => "Before After Market",
                            "currency" => "Currency",
                            "epsActual" => "EPS Actual",
                            "epsEstimate" => "EPS Estimate",
                            "epsDifference" => "EPS Difference",
                            "surprisePercent" => "Surprise Percent"
                        ),
                    ),
                ),
                "Earnings->Trend" => array(
                    "Earnings" => array(
                        "Trend" => array(
                            "_timeline_Trend" => "quarterly",
                            "period" => "Period",
                            "growth" => "Growth",
                            "earningsEstimateAvg" => "Earnings Estimate Avg",
                            "earningsEstimateLow" => "Earnings Estimate Low",
                            "earningsEstimateHigh" => "Earnings Estimate High",
                            "earningsEstimateYearAgoEps" => "Earnings Estimate Year Ago EPS",
                            "earningsEstimateNumberOfAnalysts" => "Earnings Estimate Number of Analysts",
                            "earningsEstimateGrowth" => "Earnings Estimate Growth",
                            "revenueEstimateAvg" => "Revenue Estimate Avg",
                            "revenueEstimateLow" => "Revenue Estimate Low",
                            "revenueEstimateHigh" => "Revenue Estimate High",
                            "revenueEstimateYearAgoEps" => "Revenue Estimate Year Ago EPS",
                            "revenueEstimateNumberOfAnalysts" => "Revenue Estimate Number of Analysts",
                            "revenueEstimateGrowth" => "Revenue Estimate Growth",
                            "epsTrendCurrent" => "EPS Trend Current",
                            "epsTrend7daysAgo" => "EPS Trend 7 days Ago",
                            "epsTrend30daysAgo" => "EPS Trend 30 days Ago",
                            "epsTrend60daysAgo" => "EPS Trend 60 days Ago",
                            "epsTrend90daysAgo" => "EPS Trend 90 days Ago",
                            "epsRevisionsUpLast7days" => "EPS Revisions up Last 7 days",
                            "epsRevisionsUpLast30days" => "EPS Revisions up Last 30 days",
                            "epsRevisionsDownLast7days" => "EPS Revisions down Last 7 days",
                            "epsRevisionsDownLast30days" => "EPS Revisions down Last 30 days"
                        ),
                    ),
                ),
                "Earnings->Annual" => array(
                    "Earnings" => array(
                        "Annual" => array(
                            "_timeline_Annual" => "yearly",
                            "epsActual" => "EPS Actual"
                        )
                    ),
                ),
                "Financials->Balance_Sheet" => array(
                    "Financials" => array(
                        "Balance_Sheet" => array(
                            "_timeline_Balance_Sheet" => "both",
                            "currency_symbol" => "Currency Symbol",
                            "totalAssets" => "Total Assets",
                            "intangibleAssets" => "Intangible Assets",
                            "earningAssets" => "Earning Assets",
                            "otherCurrentAssets" => "Other Current Assets",
                            "totalLiab" => "Total Liab",
                            "totalStockholderEquity" => "Total Stockholder Equity",
                            "deferredLongTermLiab" => "Deferred Long Term Liab",
                            "otherCurrentLiab" => "Other Current Liab",
                            "commonStock" => "Common Stock",
                            "retainedEarnings" => "Retained Earnings",
                            "otherLiab" => "Other Liab",
                            "goodWill" => "Good Will",
                            "otherAssets" => "Other Assets",
                            "cash" => "Cash",
                            "totalCurrentLiabilities" => "Total Current Liabilities",
                            "netDebt" => "Net Debt",
                            "shortTermDebt" => "Short Term Debt",
                            "shortLongTermDebt" => "Short Long Term Debt",
                            "shortLongTermDebtTotal" => "Short Long Term Debt Total",
                            "otherStockholderEquity" => "Other Stockholder Equity",
                            "propertyPlantEquipment" => "Property Plant Equipment",
                            "totalCurrentAssets" => "Total Current Assets",
                            "longTermInvestments" => "Long Term Investments",
                            "netTangibleAssets" => "Net Tangible Assets",
                            "shortTermInvestments" => "Short Term Investments",
                            "netReceivables" => "Net Receivables",
                            "longTermDebt" => "Long TermDebt",
                            "inventory" => "Inventory",
                            "accountsPayable" => "Accounts Payable",
                            "totalPermanentEquity" => "Total Permanent Equity",
                            "noncontrollingInterestInConsolidatedEntity" => "Non Controlling Interest In Consolidated Entity",
                            "temporaryEquityRedeemableNoncontrollingInterests" => "Temporary Equity Redeemable Non Controlling Interests",
                            "accumulatedOtherComprehensiveIncome" => "Accumulated Other Comprehensive Income",
                            "additionalPaidInCapital" => "Additional Paid In Capital",
                            "commonStockTotalEquity" => "Common Stock Total Equity",
                            "preferredStockTotalEquity" => "Preferred Stock Total Equity",
                            "retainedEarningsTotalEquity" => "Retained Earnings Total Equity",
                            "treasuryStock" => "Treasury Stock",
                            "accumulatedAmortization" => "Accumulated Amortization",
                            "nonCurrrentAssetsOther" => "Non Current Assets Other",
                            "deferredLongTermAssetCharges" => "Deferred Long Term Asset Charges",
                            "nonCurrentAssetsTotal" => "Non Current Assets Total",
                            "capitalLeaseObligations" => "Capital Lease Obligations",
                            "longTermDebtTotal" => "Long Term Debt Total",
                            "nonCurrentLiabilitiesOther" => "Non Current Liabilities Other",
                            "nonCurrentLiabilitiesTotal" => "Non Current Liabilities Total",
                            "negativeGoodwill" => "Negative Goodwill",
                            "warrants" => "Warrants",
                            "preferredStockRedeemable" => "Preferred Stock Redeemable",
                            "capitalSurpluse" => "Capital Surplus",
                            "liabilitiesAndStockholdersEquity" => "Liabilities And Stockholders Equity",
                            "cashAndShortTermInvestments" => "Cash AndShort Term Investments",
                            "propertyPlantAndEquipmentGross" => "Property Plant And Equipment Gross",
                            "propertyPlantAndEquipmentNet" => "Property Plant And Equipment Net",
                            "accumulatedDepreciation" => "Accumulated Depreciation",
                            "netWorkingCapital" => "Net Working Capital",
                            "netInvestedCapital" => "Net Invested Capital",
                            "commonStockSharesOutstanding" => "Common Stock Shares Outstanding",
                        ),
                    ),
                ),
                "Financials->Cash_Flow" => array(
                    "Financials" => array(
                        "Cash_Flow" => array(
                            "_timeline_Cash_Flow" => "both",
                            "currency_symbol" => "Currency Symbol",
                            "investments" => "Investments",
                            "changeToLiabilities" => "Change to Liabilities",
                            "totalCashflowsFromInvestingActivities" => "Total Cash Flows From Investing Activities",
                            "netBorrowings" => "Net Borrowings",
                            "totalCashFromFinancingActivities" => "Total Cash from Financing Activities",
                            "changeToOperatingActivities" => "Change to Operating Activities",
                            "netIncome" => "Net Income",
                            "changeInCash" => "Change in Cash",
                            "beginPeriodCashFlow" => "Begin Period Cash Flow",
                            "endPeriodCashFlow" => "End Period Cash Flow",
                            "totalCashFromOperatingActivities" => "Total Cash From Operating Activities",
                            "depreciation" => "Depreciation",
                            "otherCashflowsFromInvestingActivities" => "Other Cash Flows from Investing Activities",
                            "dividendsPaid" => "Dividends Paid",
                            "changeToInventory" => "Change to Inventory",
                            "changeToAccountReceivables" => "Change to Account Receivables",
                            "salePurchaseOfStock" => "Sale Purchase of Stock",
                            "otherCashflowsFromFinancingActivities" => "Other Cash Flows from Financing Activities",
                            "changeToNetincome" => "Change to Net Income",
                            "capitalExpenditures" => "Capital Expenditures",
                            "changeReceivables" => "Change Receivables",
                            "cashFlowsOtherOperating" => "Cash Flows Other Operating",
                            "exchangeRateChanges" => "Exchange Rate Changes",
                            "cashAndCashEquivalentsChanges" => "Cash and Cash Equivalents Changes",
                            "changeInWorkingCapital" => "Change in Working Capital",
                            "otherNonCashItems" => "Other Non Cash Items",
                            "freeCashFlow" => "Free Cash Flow",
                        ),
                    ),
                ),
                "Financials->Income_Statement" => array(
                    "Financials" => array(
                        "Income_Statement" => array(
                            "_timeline_Income_Statement" => "both",
                            "currency_symbol" => "Currency Symbol",
                            "researchDevelopment" => "Research Development",
                            "effectOfAccountingCharges" => "Effect of Accounting Charges",
                            "incomeBeforeTax" => "Income Before Tax",
                            "minorityInterest" => "Minority Interest",
                            "netIncome" => "Net Income",
                            "sellingGeneralAdministrative" => "Selling General Administrative",
                            "sellingAndMarketingExpenses" => "Selling and Marketing Expenses",
                            "grossProfit" => "Gross Profit",
                            "reconciledDepreciation" => "Reconciled Depreciation",
                            "ebit" => "EBIT",
                            "ebitda" => "EBITDA",
                            "depreciationAndAmortization" => "Depreciation and Amortization",
                            "nonOperatingIncomeNetOther" => "Non Operating Income Net Other",
                            "operatingIncome" => "Operating Income",
                            "otherOperatingExpenses" => "Other Operating Expenses",
                            "interestExpense" => "Interest Expense",
                            "taxProvision" => "Tax Provision",
                            "interestIncome" => "Interest Income",
                            "netInterestIncome" => "Net Interest Income",
                            "extraordinaryItems" => "Extraordinary Items",
                            "nonRecurring" => "Non Recurring",
                            "otherItems" => "Other Items",
                            "incomeTaxExpense" => "Income Tax Expense",
                            "totalRevenue" => "Total Revenue",
                            "totalOperatingExpenses" => "Total Operating Expenses",
                            "costOfRevenue" => "Cost of Revenue",
                            "totalOtherIncomeExpenseNet" => "Total Other Income Expense Net",
                            "discontinuedOperations" => "Discontinued Operations",
                            "netIncomeFromContinuingOps" => "Net Income From Continuing Ops",
                            "netIncomeApplicableToCommonShares" => "Net Income Applicable to Common Shares",
                            "preferredStockAndOtherAdjustments" => "Preferred Stock and Other Adjustments",
                        )
                    )
                )
            );
        }
    }
}


if(class_exists('EOD_API')) {
    function eod_api()
    {
        global $eod_api;

        // Instantiate only once.
        if ( ! isset( $eod_api ) ) {
            $eod_api = new EOD_API();
        }
        return $eod_api;
    }

    // Instantiate.
    eod_api();
}