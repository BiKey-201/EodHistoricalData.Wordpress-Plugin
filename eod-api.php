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
         * Get library of fundamental data labels
         * @return array
         */
        public function get_fd_lib()
        {
            return array(
                "General" => array(
                    "Code" => "Code",
                    "Type" => "Type",
                    "Name" => "Name",
                    "Exchange" => "Exchange",
                    "CurrencyCode" => "Currency Code",
                    "CurrencyName" => "Currency Name",
                    "CurrencySymbol" => "Currency Symbol",
                    "CountryName" => "Country Name",
                    "CountryISO" => "Country ISO",
                    "ISIN" => "ISIN",
                    "CUSIP" => "CUSIP",
                    "CIK" => "CIK",
                    "EmployerIdNumber" => "Employer Id Number",
                    "FiscalYearEnd" => "Fiscal Year End",
                    "IPODate" => "IPO Date",
                    "InternationalDomestic" => "International Domestic",
                    "Sector" => "Sector",
                    "Industry" => "Industry",
                    "GicSector" => "Gic Sector",
                    "GicGroup" => "Gic Group",
                    "GicIndustry" => "Gic Industry",
                    "GicSubIndustry" => "Gic Sub Industry",
                    "HomeCategory" => "Home Category",
                    "IsDelisted" => "Is Delisted",
                    "Description" => "Description",
                    "Address" => "Address",
                    "AddressData" => array(
                        "Street" => "Street",
                        "City" => "City",
                        "State" => "State",
                        "Country" => "Country",
                        "ZIP" => "ZIP"
                    ),
                    "Listings" => "Listings",
                    "Officers" => "Officers",
                    "Phone" => "Phone",
                    "WebURL" => "WebURL",
                    "LogoURL" => "LogoURL",
                    "FullTimeEmployees" => "FullTime Employees",
                    "UpdatedAt" => "Updated At",
                ),
                "Highlights" => array(
                    "MarketCapitalization" => "Market Capitalization",
                    "MarketCapitalizationMln" => "Market Capitalization Mln",
                    "EBITDA" => "EBITDA",
                    "PERatio" => "PE Ratio",
                    "PEGRatio" => "PEG Ratio",
                    "WallStreetTargetPrice" => "Wall Street Target Price",
                    "BookValue" => "Book Value",
                    "DividendShare" => "Dividend Share",
                    "DividendYield" => "Dividend Yield",
                    "EarningsShare" => "Earnings Share",
                    "EPSEstimateCurrentYear" => "EPS Estimate Current Year",
                    "EPSEstimateNextYear" => "EPS Estimate Next Year",
                    "EPSEstimateNextQuarter" => "EPS Estimate Next Quarter",
                    "EPSEstimateCurrentQuarter" => "EPS Estimate Current Quarter",
                    "MostRecentQuarter" => "Most Recent Quarter",
                    "ProfitMargin" => "Profit Margin",
                    "OperatingMarginTTM" => "Operating Margin TTM",
                    "ReturnOnAssetsTTM" => "Return On Assets TTM",
                    "ReturnOnEquityTTM" => "Return On Equity TTM",
                    "RevenueTTM" => "RevenueTTM",
                    "RevenuePerShareTTM" => "Revenue Per Share TTM",
                    "QuarterlyRevenueGrowthYOY" => "Quarterly Revenue Growth YOY",
                    "GrossProfitTTM" => "Gross Profit TTM",
                    "DilutedEpsTTM" => "Diluted Eps TTM",
                    "QuarterlyEarningsGrowthYOY" => "Quarterly Earnings Growth YOY",
                ),
                "Valuation" => array(
                    "TrailingPE" => "Trailing PE",
                    "ForwardPE" => "Forward PE",
                    "PriceSalesTTM" => "Price Sales TTM",
                    "PriceBookMRQ" => "Price Book MRQ",
                    "EnterpriseValue" => "Enterprise Value",
                    "EnterpriseValueRevenue" => "Enterprise Value Revenue",
                    "EnterpriseValueEbitda" => "Enterprise ValueEbitda",
                ),
                "SharesStats" => array(
                    "SharesOutstanding" => "Shares Outstanding",
                    "SharesFloat" => "Shares Float",
                    "PercentInsiders" => "Percent Insiders",
                    "PercentInstitutions" => "Percent Institutions",
                    "SharesShort" => "Shares Short",
                    "SharesShortPriorMonth" => "Shares Short Prior Month",
                    "ShortRatio" => "Short Ratio",
                    "ShortPercentOutstanding" => "Short Percent Outstanding",
                    "ShortPercentFloat" => "Short Percent Float",
                ),
                "Technicals" => array(
                    "Beta" => "Beta",
                    "52WeekHigh" => "52 Week High",
                    "52WeekLow" => "52 Week Low",
                    "50DayMA" => "50 Day MA",
                    "200DayMA" => "200 Day MA",
                    "SharesShort" => "Shares Short",
                    "SharesShortPriorMonth" => "Shares Short Prior Month",
                    "ShortRatio" => "Short Ratio",
                    "ShortPercent" => "Short Percent",
                ),
                "SplitsDividends" => array(
                    "ForwardAnnualDividendRate" => "Forward Annual Dividend Rate",
                    "ForwardAnnualDividendYield" => "Forward Annual Dividend Yield",
                    "PayoutRatio" => "Payout Ratio",
                    "DividendDate" => "Dividend Date",
                    "ExDividendDate" => "Ex Dividend Date",
                    "LastSplitFactor" => "Last Split Factor",
                    "LastSplitDate" => "Last Split Date",
                    "NumberDividendsByYear" => "Number Dividends By Year",
                ),
                "AnalystRatings" => array(
                    "Rating" => "Rating",
                    "TargetPrice" => "Target Price",
                    "StrongBuy" => "Strong Buy",
                    "Buy" => "Buy",
                    "Hold" => "Hold",
                    "Sell" => "Sell",
                    "StrongSell" => "StrongSell",
                ),
                "Holders" => array(
                    "Institutions" => "Institutions",
                    "Funds" => "Funds",
                ),
                "InsiderTransactions" => "InsiderTransactions",
                "ESGScores" => array(
                    "Disclaimer" => "Disclaimer",
                    "RatingDate" => "Rating Date",
                    "TotalEsg" => "Total ESG",
                    "TotalEsgPercentile" => "Total ESG Percentile",
                    "EnvironmentScore" => "Environment Score",
                    "EnvironmentScorePercentile" => "Environment Score Percentile",
                    "SocialScorePercentile" => "Social Score Percentile",
                    "GovernanceScore" => "Governance Score",
                    "GovernanceScorePercentile" => "Governance Score Percentile",
                    "ControversyLevel" => "Controversy Level",
                    "ActivitiesInvolvement" => "Activities Involvement",
                ),
            );
        }

        /**
         * Get library of financials labels
         * @return array
         */
        public function get_financials_lib()
        {
            return array(
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
                    "Annual" => array(
                        "_timeline_Annual" => "yearly",
                        "epsActual" => "EPS Actual"
                    )
                ),
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
                    "_timeline_Income_Statement" => "both",
                    "Income_Statement" => array(
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