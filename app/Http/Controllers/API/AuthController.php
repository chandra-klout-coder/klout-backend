<?php

namespace App\Http\Controllers\API;

use App\Models\Name;
use App\Models\User;
use App\Models\City;
use App\Models\State;
use Ramsey\Uuid\Uuid;
use App\Models\Country;
use App\Models\Company;
use App\Models\UserOtp;
use App\Models\JobTitle;
use App\Models\Industry;
use App\Models\SkillsData;
use App\Models\Subscriber;
use App\Models\CompanyData;
use App\Models\IndustryData;
use App\Models\JobTitleData;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\SmsServices;
use App\Models\PasswordReset;
use App\Services\EmailService;
use App\Mail\ResetPasswordMail;
use App\Models\ContactMessage;
use App\Models\WebsiteSetting;
use App\Models\SponsorshipPackages;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\UnassignedData;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    //Mobile App
    public function get_industries()
    {
        $industries = Industry::all();

        if ($industries) {
            return response()->json([
                'status' => 200,
                'message' => 'All Industries',
                'data' => $industries
            ]);
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Data not Found'
            ]);
        }
    }

    public function get_job_titles()
    {
        $JobTitleData = JobTitle::all();

        if ($JobTitleData) {
            return response()->json([
                'status' => 200,
                'message' => 'All Job Titles',
                'data' => $JobTitleData
            ]);
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Data not Found'
            ]);
        }
    }

    public function get_companies()
    {
        $CompanyData = Company::all();

        if ($CompanyData) {
            return response()->json([
                'status' => 200,
                'message' => 'All Companies',
                'data' => $CompanyData
            ]);
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Data not Found'
            ]);
        }
    }
    public function others_unasssigned_data(Request $request)
    {
        $user_id = $request->input('user_id');
        $other_id = $request->input('other_id');
        $type = $request->input('type');
        $value = $request->input('value');

        if (isset($type) && !empty($type) && !empty($other_id)) {

            $data = new UnassignedData();

            //$city->uuid = Uuid::uuid4()->toString();
            $data->user_id = !empty($request->user_id) ? $request->user_id : "0";
            $data->other_id = $request->other_id;
            $data->type = $request->type;
            $data->value = !empty($request->value) ? $request->value : "";
            $success = $data->save();

            if ($success) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Data Saved Successfully.'
                ]);
            } else {
                return response()->json([
                    'status' => 422,
                    'message' => 'Something Went Wrong.Please try again later.'
                ]);
            }
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Incorrect Data'
            ]);
        }
    }

    //City wise Event 
    public function city_wise_event(Request $request)
    {
        $city_id = $request->input('city_id');

        $allEvents = Event::where('city', $city_id)->get();

        if ($allEvents) {
            return response()->json([
                'status' => 200,
                'message' => 'All City Wise Event',
                'data' => $allEvents
            ]);
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Data not Found'
            ]);
        }
    }

    //Mapping of Industries
    public function industry(Request $request)
    {
        $names = IndustryData::all();

        $rootNames = $request->input('industry_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);
            } else {

                foreach ($names as $name) {

                    $maxSimilarity = 0;

                    similar_text($name->name, $rootNames, $similarity);

                    if ($similarity > $maxSimilarity) {
                        $maxSimilarity = $similarity;
                    }

                    if ($maxSimilarity >= 30 && $maxSimilarity <= 90) {

                        $nameO = IndustryData::find($name->id);

                        if ($nameO) {
                            $nameO->children()->create([
                                'name' => $rootNames,
                            ]);
                        }

                        unset($rootNames);

                        break;
                    }

                    if ($maxSimilarity < 30) {

                        IndustryData::create([
                            'name' => $rootNames,
                            'parent_id' => 0,
                        ]);

                        unset($rootNames);

                        break;
                    }

                    $maxSimilarity = $similarity = 0;
                }
            }
        } else {

            IndustryData::create([
                'name' => $rootNames,
                'parent_id' => 0,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Industry Added Successfully.'
        ]);
    }

    //Mapping of Companies
    public function company(Request $request)
    {
        $names = CompanyData::all();

        $rootNames = $request->input('company_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);
            } else {

                foreach ($names as $name) {

                    $maxSimilarity = 0;

                    similar_text($name->name, $rootNames, $similarity);

                    if ($similarity > $maxSimilarity) {
                        $maxSimilarity = $similarity;
                    }

                    if ($maxSimilarity >= 30 && $maxSimilarity <= 90) {

                        $nameO = Name::find($name->id);

                        if ($nameO) {
                            $nameO->children()->create([
                                'name' => $rootNames,
                            ]);
                        }

                        unset($rootNames);

                        break;
                    }

                    if ($maxSimilarity < 30) {

                        Name::create([
                            'name' => $rootNames,
                            'parent_id' => 0,
                        ]);

                        unset($rootNames);

                        break;
                    }

                    $maxSimilarity = $similarity = 0;
                }
            }
        } else {

            Name::create([
                'name' => $rootNames,
                'parent_id' => 0,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Company Added Successfully.'
        ]);
    }

    //Mapping of Job Titles
    public function job_title(Request $request)
    {
        $names = JobTitleData::all();

        $rootNames = $request->input('job_title_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);
            } else {

                foreach ($names as $name) {

                    $maxSimilarity = 0;

                    similar_text($name->name, $rootNames, $similarity);

                    if ($similarity > $maxSimilarity) {
                        $maxSimilarity = $similarity;
                    }

                    if ($maxSimilarity >= 30 && $maxSimilarity <= 90) {

                        $nameO = Name::find($name->id);

                        if ($nameO) {
                            $nameO->children()->create([
                                'name' => $rootNames,
                            ]);
                        }

                        unset($rootNames);

                        break;
                    }

                    if ($maxSimilarity < 30) {

                        Name::create([
                            'name' => $rootNames,
                            'parent_id' => 0,
                        ]);

                        unset($rootNames);

                        break;
                    }

                    $maxSimilarity = $similarity = 0;
                }
            }
        } else {

            Name::create([
                'name' => $rootNames,
                'parent_id' => 0,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Job Title Added Successfully.'
        ]);
    }

    //Mapping of Skills
    public function skills(Request $request)
    {
        $names = SkillsData::all();

        $rootNames = trim($request->input('skills_name'));

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);

                return response()->json([
                    'status' => 201,
                    'message' => 'Skills Already Exists.'
                ]);
            } else {

                SkillsData::create([
                    'name' => $rootNames,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Skills Added Successfully.'
                ]);
            }
        } else {

            SkillsData::create([
                'name' => $rootNames,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Skills Added Successfully.'
            ]);
        }
    }

    //Mapping Company
    public function country(Request $request)
    {
        $names = Country::all();

        $rootNames = $request->input('country_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);

                return response()->json([
                    'status' => 201,
                    'message' => 'Country Already Exists.'
                ]);
            } else {

                Country::create([
                    'name' => $rootNames,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Country Added Successfully.'
                ]);
            }
        } else {

            Country::create([
                'name' => $rootNames,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Country Added Successfully.'
            ]);
        }
    }

    //Mapping State
    public function state(Request $request)
    {
        $names = State::all();

        $rootNames = $request->input('state_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);

                return response()->json([
                    'status' => 201,
                    'message' => 'State Already Exists.'
                ]);
            } else {

                State::create([
                    'name' => $rootNames,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'State Added Successfully.'
                ]);
            }
        } else {

            State::create([
                'name' => $rootNames,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'State Added Successfully.'
            ]);
        }
    }

    //Mapping City
    public function city(Request $request)
    {
        $names = City::all();

        $rootNames = $request->input('city_name');

        if (isset($names) && count($names) > 0) {

            if ($names->contains('name', $rootNames)) {

                unset($rootNames);

                return response()->json([
                    'status' => 201,
                    'message' => 'City Already Exists.'
                ]);
            } else {

                City::create([
                    'name' => $rootNames,
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'City Added Successfully.'
                ]);
            }
        } else {

            City::create([
                'name' => $rootNames,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'City Added Successfully.'
            ]);
        }
    }

    public function test()
    {
        $companies = ["Accolite software", "Accord software", "Adobe", "Able", "Abstract", "Acko", "Accredible", "Activision Blizzard", "Affinidi", "Agile Solutions", "Agnikul Cosmos", "Airbase", "Airbnb", "Airbus", "Airtel x labs", "Ajio", "Akamai", "Alstom", "Alpha-grep", "Alphonso", "Amadeus labs", "Amagi", "AMD", "Amazon", "Amdocs", "American express", "Amway", "Angelone", "Analog Devices", "Ansys", "Apna", "App Dynamics", "Appen", "Apple", "AppInventiv", "Applied Materials", "Aptiv", "AQR", "Arcesium", "Arista Networks", "Aryaka networks", "Asteria Aerospace Ltd", "ASML", "Athena Health", "Atlan", "Atlassian", "Automatic Data Processing", "Auzmor", "Avail finance", "Avalara", "Avaya", "Barclays", "Bain & Co", "BARC India", "bankbazaar", "Bazaarvoice", "BCG", "Bellatrix Aerospace", "Benchmark", "Better", "BharatPe", "Bidgely", "BigBasket", "BlackBuck", "Blackrock", "Block Inc", "Bloomberg LP", "BlueJeans", "Bluestacks", "BMC software", "BNY Mellon", "Boeing", "Booking.com", "Bosch", "Bottemline Technologies", "Bounce", "Box", "Brahmastra Aerospace", "Browser stack", "Broadcom", "BukuWarung", "ByteDance", "Cadence", "Capillary", "Capital One", "CarDekho", "Careem", "CarWale", "Cashfree", "Cimpress", "Celigo", "Cerner", "Chargebee", "Checkout.com", "Chronus", "Cisco", "Citadel", "Citadel Securities", "Citrix", "Classplus", "ClearGlass", "Cleartrip", "Cloudera", "Codenation innovation labs", "CodeParva Technologies Pvt Ltd", "CodingNinjas", "Cognizant", "CoinBase", "CoinDCX", "Coinswitch kuber", "Commvault", "Continental", "Contra", "Coupang", "Cradlepoint", "Cred", "Credit Suisse", "Crestron", "Crowdstrike", "CSS Corp", "cure.fit", "Cvent", "DailyHunt", "Dashlane", "Databricks", "Dataminr", "DBS", "D. E. Shaw & Co.", "DealShare", "Delhivery", "Dell", "Deutsche Bank", "Dhruva Space", "Dialpad", "Directi", "digit", "Discord", "Discovery inc", "Disney", "DoorDash", "DP World", "DRDO", "Dream11", "droom", "Dropbox", "Druva", "Dukaan", "Dunzo", "DuPont", "EA Games", "Enfussion", "Envestnet Yoodlee", "Epam", "Ericsson", "Eurofins", "Equinix", "EXL Healthcare", "Expedia", "EY", "EyeROV", "F5", "factset", "FamPay", "Fidelity investments", "FireEye inc", "Fischer Jordan", "fiserv", "Flexport", "Flipkart", "FlyFin", "fincover", "Fractal", "Frappe Technologies", "FreeCharge", "Freshworks", "Furlenco", "fyle", "Fico", "Gartner", "Garuda Aerospace private Ltd", "GeeksForGeeks", "GE", "GE Healthcare", "GeekyAnts", "Genpact", "Ghost", "Github", "Gitlab", "GoDaddy", "GoDigit", "Godrej Aerospace", "Gojek", "Goldman Sachs", "Google", "Global Logic", "Grab", "Gravitont Rading", "Groupon", "Grofers", "Groww", "Hackerearth", "HackerRank", "Hashedin", "Hashnode", "HBO", "HealthAsyst", "Healthify me", "HERE", "Hexagone", "Hotstar", "Honeywell", "HP", "IBM", "IdeaForge", "IHS Markit", "Impact Analytics", "Indeed", "India Mart", "Infor", "Informatica", "Infospoke", "Inmobi", "Innovaccer", "Intel", "Intellika", "Intuit", "IP Infusion", "ISRO", "iQuanti", "Jaguar", "Jio", "JM Financial", "JP Morgan", "Juniper networks", "Jupiter money", "Juspay", "Jumbotail", "Kantar", "Kesari bharat", "Keyence", "Keyvalue", "Khatabook", "khoros", "KLA Tencor", "Koch", "LambdaTest", "Lam Research", "Land rover", "Lenskart", "Leap Finance", "Licious", "Liebherr", "LinkedIn", "LogicFruit", "Logicmonitor", "Lowe's companies, inc", "Magicpin", "MakeMyTrip", "Mastercard", "Mastery", "Mathworks", "Maq Software", "McKinsey", "Media.net", "Meta", "Meesho", "Memory", "Micron", "Microsoft", "MindTickle", "MobiKwik", "Morgan Stanley", "Mount talent", "MPL", "MTX", "Myntra", "Nagarro", "NASDAQ", "National instruments", "NatWest Group", "navi", "NCR Corporation", "NetApp", "Netcracker", "Netflix", "Netmeds", "Nike", "Ninjacart", "Nokia", "nurture.farm", "Nutanix", "Nvidia", "Nykaa", "Ninjacart", "Obvious", "Ocrolus", "Ola", "Olx", "Oracle", "OYO", "Observe.ai", "OpenText", "Optum", "Optym", "Palo Alto Networks", "Park+", "Paypal", "Paytm", "PayU", "Pazo", "PeopleStrong", "persistent systems", "PharmEasy", "Phillips", "Phonepe", "Playment", "Planful", "Polygon Technology", "postman", "Practo", "priceline", "Principal", "Privado.ai", "Providence Healthcare", "Protegrity", "Proximity Labs", "Prodapt", "Publicis Sapient", "PubMatic", "Qualcomm", "Quantiphi", "QuickSell", "Quora", "Ramp", "Razorpay", "Red hat", "Reelo", "Reprise", "Rippling", "Rivigo", "Rocket Companies", "Rockstar Games", "Rubrik", "Saavan", "Sabre corporation", "SalaryBox", "Salesforce", "Samsung", "SAP", "Schneider Electric", "SendinBlue", "SerpApi", "ServiceNow", "Shaadi.com", "ShareChat", "Shell", "Shipsy", "Shopee", "Shopify", "Siemens", "Siemens Healthineers", "Sigmoid", "SkillVertex", "Skyroot Aerospace", "Sling Media", "Smith Detection", "Sony", "Spinny", "Sprinklr", "Squadstack", "Stripe", "Sureify", "Swiggy", "Synopsys", "Target", "TATA Advanced Sysytems Ltd", "TATA nexarc", "TE Connectivity", "TEK Systems", "Tekion corp", "Tencent", "Tesla", "Teradata", "Texas Instruments", "TSMC", "ThoughtSpot", "ThoughtWorks", "Topcoder", "Toptal", "tower research capital", "Treebo Hotels", "Turvo", "Twilio", "Twitter", "Uber", "Ubisoft", "Udaan", "Ultimate Kronos Group", "unacadamy", "Unicommerce", "Unisys", "Upgrad", "Upstox", "Upwork", "Urban company", "Valuefy", "Viasat", "Vicara", "Visa", "Vmware", "Vogo", "Walmart", "Warner Bros.", "Wells Fargo", "Western Digital", "Whatfix", "Wooqer", "worldQuant", "Xiaomi", "Xicom Technologies", "Yahoo", "yellow.ai", "yugabyte", "Yulu Bikes", "zerodha", "Zeta", "ZivaMe", "zoho", "Zomato", "ZoomCar", "ZS", "zerodha", "Zeta", "ZivaMe", "zoho", "Zomato", "ZoomCar", "ZS"];

        $industries = ["Industry", "Accounting ", "Airlines/Aviation", "Alternative Dispute Resolution", "Alternative Medicine", "Animation", "Apparel/Fashion", "Architecture/Planning", "Arts/Crafts", "Automotive", "Aviation/Aerospace", "Banking/Mortgage", "Biotechnology/Greentech", "Broadcast Media", "Building Materials", "Business Supplies/Equipment", "Capital Markets/Hedge Fund/Private Equity", "Chemicals", "Civic/Social Organization", "Civil Engineering", "Commercial Real Estate", "Computer Games", "Computer Hardware", "Computer Networking", "Computer Software/Engineering", "Computer/Network Security", "Construction", "Consumer Electronics", "Consumer Goods", "Consumer Services", "Cosmetics", "Dairy", "Defense/Space", "Design", "E-Learning", "Education Management", "Electrical/Electronic Manufacturing", "Entertainment/Movie Production", "Environmental Services", "Events Services", "Executive Office", "Facilities Services", "Farming", "Financial Services", "Fine Art", "Fishery", "Food Production", "Food/Beverages", "Fundraising", "Furniture", "Gambling/Casinos", "Glass/Ceramics/Concrete", "Government Administration", "Government Relations", "Graphic Design/Web Design", "Health/Fitness", "Higher Education/Acadamia", "Hospital/Health Care", "Hospitality", "Human Resources/HR", "Import/Export", "Individual/Family Services", "Industrial Automation", "Information Services", "Information Technology/IT", "Insurance", "International Affairs", "International Trade/Development", "Internet", "Investment Banking/Venture", "Investment Management/Hedge Fund/Private Equity", "Judiciary", "Law Enforcement", "Law Practice/Law Firms", "Legal Services", "Legislative Office", "Leisure/Travel", "Library", "Logistics/Procurement", "Luxury Goods/Jewelry", "Machinery", "Management Consulting", "Maritime", "Market Research", "Marketing/Advertising/Sales", "Mechanical or Industrial Engineering", "Media Production", "Medical Equipment", "Medical Practice", "Mental Health Care", "Military Industry", "Mining/Metals", "Motion Pictures/Film", "Museums/Institutions", "Music", "Nanotechnology", "Newspapers/Journalism", "Non-Profit/Volunteering", "Oil/Energy/Solar/Greentech", "Online Publishing", "Other Industry", "Outsourcing/Offshoring", "Package/Freight Delivery", "Packaging/Containers", "Paper/Forest Products", "Performing Arts", "Pharmaceuticals", "Philanthropy", "Photography", "Plastics", "Political Organization", "Primary/Secondary Education", "Printing", "Professional Training", "Program Development", "Public Relations/PR", "Public Safety", "Publishing Industry", "Railroad Manufacture", "Ranching", "Real Estate/Mortgage", "Recreational Facilities/Services", "Religious Institutions", "Renewables/Environment", "Research Industry", "Restaurants", "Retail Industry", "Security/Investigations", "Semiconductors", "Shipbuilding", "Sporting Goods", "Sports", "Staffing/Recruiting", "Supermarkets", "Telecommunications", "Textiles", "Think Tanks", "Tobacco", "Translation/Localization", "Transportation", "Utilities", "Venture Capital/VC", "Veterinary", "Warehousing", "Wholesale", "Wine/Spirits", "Wireless", "Writing/Editing", "Others"];

        $job_titles = ["Marketing Specialist", "Marketing Manager", "Marketing Director", "Graphic Designer", "Marketing Research Analyst", "Marketing Communications Manager", "Marketing Consultant", "Product Manager", "Public Relations", "Social Media Assistant", "Brand Manager", "SEO Manager", "Content Marketing Manager", "Copywriter", "Media Buyer", "Digital Marketing Manager", "eCommerce Marketing Specialist", "Brand Strategist", "Vice President of Marketing", "Media Relations Coordinator", "Administrative Assistant", "Receptionist", "Office Manager", "Auditing Clerk", "Bookkeeper", "Account Executive", "Branch Manager", "Business Manager", "Quality Control Coordinator", "Administrative Manager", "Chief Executive Officer", "Business Analyst", "Risk Manager", "Human Resources", "Office Assistant", "Secretary", "Office Clerk", "File Clerk", "Account Collector", "Administrative Specialist", "Executive Assistant", "Program Administrator", "Program Manager", "Administrative Analyst", "Data Entry", "CEO—Chief Executive Officer", "COO—Chief Operating Officer", "CFO—Chief Financial Officer", "CIO—Chief Information Officer", "CTO—Chief Technology Officer", "CMO—Chief Marketing Officer", "CHRO—Chief Human Resources Officer", "CDO—Chief Data Officer", "CPO—Chief Product Officer", "CCO—Chief Customer Officer", "Team Leader", "Manager", "Assistant Manager", "Executive", "Director", "Coordinator", "Administrator", "Controller", "Officer", "Organizer", "Supervisor", "Superintendent", "Head", "Overseer", "Chief", "Foreman", "Controller", "Principal", "President", "Lead", "Computer Scientist", "IT Professional", "UX Designer & UI Developer", "SQL Developer", "Web Designer", "Web Developer", "Help Desk Worker/Desktop Support", "Software Engineer", "Data Entry", "DevOps Engineer", "Computer Programmer", "Network Administrator", "Information Security Analyst", "Artificial Intelligence Engineer", "Cloud Architect", "IT Manager", "Technical Specialist", "Application Developer", "Chief Technology Officer (CTO)", "Chief Information Officer (CIO)", "Sales Associate", "Sales Representative", "Sales Manager", "Retail Worker", "Store Manager", "Sales Representative", "Sales Manager", "Real Estate Broker", "Sales Associate", "Cashier", "Store Manager", "Account Executive", "Account Manager", "Area Sales Manager", "Direct Salesperson", "Director of Inside Sales", "Outside Sales Manager", "Sales Analyst", "Market Development Manager", "B2B Sales Specialist", "Sales Engineer", "Merchandising Associate", "Construction Worker", "Taper", "Plumber", "Heavy Equipment Operator", "Vehicle or Equipment Cleaner", "Carpenter", "Electrician", "Painter", "Welder", "Handyman", "Boilermaker", "Crane Operator", "Building Inspector", "Pipefitter", "Sheet Metal Worker", "Iron Worker", "Mason", "Roofer", "Solar Photovoltaic Installer", "Well Driller", "CEO", "Proprietor", "Principal", "Owner", "President", "Founder", "Administrator", "Director", "Managing Partner", "Managing Member", "Board of Directors", "C-Level or C-Suite.", "Shareholders", "Managers", "Supervisors", "Front-Line Employees", "Quality Control", "Human Resources", "Accounting Staff", "Marketing Staff", "Purchasing Staff", "Shipping and Receiving Staff", "Office Manager", "Receptionist", "Virtual Assistant", "Customer Service", "Customer Support", "Concierge", "Help Desk", "Customer Service Manager", "Technical Support Specialist", "Account Representative", "Client Service Specialist", "Customer Care Associate", "Operations Manager", "Operations Assistant", "Operations Coordinator", "Operations Analyst", "Operations Director", "Vice President of Operations", "Operations Professional", "Scrum Master", "Continuous Improvement Lead", "Continuous Improvement Consultant", "Credit Authorizer", "Benefits Manager", "Credit Counselor", "Accountant", "Bookkeeper", "Accounting Analyst", "Accounting Director", "Accounts Payable/Receivable Clerk", "Auditor", "Budget Analyst", "Controller", "Financial Analyst", "Finance Manager", "Economist", "Payroll Manager", "Payroll Clerk", "Financial Planner", "Financial Services Representative", "Finance Director", "Commercial Loan Officer", "Engineer", "Mechanical Engineer", "Civil Engineer", "Electrical Engineer", "Assistant Engineer", "Chemical Engineer", "Chief Engineer", "Drafter", "Engineering Technician", "Geological Engineer", "Biological Engineer", "Maintenance Engineer", "Mining Engineer", "Nuclear Engineer", "Petroleum Engineer", "Plant Engineer", "Production Engineer", "Quality Engineer", "Safety Engineer", "Sales Engineer", "Chief People Officer", "VP of Miscellaneous Stuff", "Chief Robot Whisperer", "Director of First Impressions", "Culture Operations Manager", "Director of Ethical Hacking", "Software Ninjaneer", "Director of Bean Counting", "Digital Overlord", "Director of Storytelling", "Researcher", "Research Assistant", "Data Analyst", "Business Analyst", "Financial Analyst", "Biostatistician", "Title Researcher", "Market Researcher", "Title Analyst", "Medical Researcher", "Mentor", "Tutor/Online Tutor", "Teacher", "Teaching Assistant", "Substitute Teacher", "Preschool Teacher", "Test Scorer", "Online ESL Instructor", "Professor", "Assistant Professor", "Others"];

        $sponsorship_packages = ["Title", "Presenting", "Co-Presenting", "Platinum", "Diamond", "Gold", "Silver", "Associate", "Custom"];

        $countries = ["Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia & Herzegovina", "Botswana", "Brazil", "British Virgin Is.", "Brunei", "Bulgaria", "Burkina Faso", "Burma", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Rep.", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo, Dem. Rep.", "Congo, Repub. of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Faroe Islands", "Fiji", "Finland", "France", "French Guiana", "French Polynesia", "Gabon", "Gambia, The", "Gaza Strip", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guernsey", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Isle of Man", "Israel", "Italy", "Jamaica", "Japan", "Jersey", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Fed. St.", "Moldova", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "N. Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russia", "Rwanda", "Saint Helena", "Saint Kitts & Nevis", "Saint Lucia", "St Pierre & Miquelon", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome & Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad & Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks & Caicos Is", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands", "Wallis and Futuna", "West Bank", "Western Sahara", "Yemen", "Zambia", "Zimbabwe"];

        $cities = ["Abohar", "Adilabad", "Agartala", "Agra", "Ahmadnagar", "Ahmedabad", "Aizawl  ", "Ajmer", "Akola", "Alappuzha", "Aligarh", "Alipurduar", "Allahabad", "Alwar", "Ambala", "Amaravati", "Amritsar", "Asansol", "Aurangabad", "Aurangabad", "Bakshpur", "Bamanpuri", "Baramula", "Barddhaman", "Bareilly", "Belgaum", "Bellary", "Bengaluru", "Bhagalpur", "Bharatpur", "Bharauri", "Bhatpara", "Bhavnagar", "Bhilai", "Bhilwara", "Bhiwandi", "Bhiwani", "Bhopal ", "Bhubaneshwar", "Bhuj", "Bhusaval", "Bidar", "Bijapur", "Bikaner", "Bilaspur", "Brahmapur", "Budaun", "Bulandshahr", "Calicut", "Chanda", "Chandigarh ", "Chennai", "Chikka Mandya", "Chirala", "Coimbatore", "Cuddalore", "Cuttack", "Daman", "Davangere", "DehraDun", "Delhi", "Dhanbad", "Dibrugarh", "Dindigul", "Dispur", "Diu", "Faridabad", "Firozabad", "Fyzabad", "Gangtok", "Gaya", "Ghandinagar", "Ghaziabad", "Gopalpur", "Gulbarga", "Guntur", "Gurugram", "Guwahati", "Gwalior", "Haldia", "Haora", "Hapur", "Haripur", "Hata", "Hindupur", "Hisar", "Hospet", "Hubli", "Hyderabad", "Imphal", "Indore", "Itanagar", "Jabalpur", "Jaipur", "Jammu", "Jamshedpur", "Jhansi", "Jodhpur", "Jorhat", "Kagaznagar", "Kakinada", "Kalyan", "Karimnagar", "Karnal", "Karur", "Kavaratti", "Khammam", "Khanapur", "Kochi", "Kohima", "Kolar", "Kolhapur", "Kolkata ", "Kollam", "Kota", "Krishnanagar", "Krishnapuram", "Kumbakonam", "Kurnool", "Latur", "Lucknow", "Ludhiana", "Machilipatnam", "Madurai", "Mahabubnagar", "Malegaon Camp", "Mangalore", "Mathura", "Meerut", "Mirzapur", "Moradabad", "Mumbai", "Muzaffarnagar", "Muzaffarpur", "Mysore", "Nagercoil", "Nalgonda", "Nanded", "Nandyal", "Nasik", "Navsari", "Nellore", "New Delhi", "Nizamabad", "Ongole", "Pali", "Panaji", "Panchkula", "Panipat", "Parbhani", "Pathankot", "Patiala", "Patna", "Pilibhit", "Porbandar", "Port Blair", "Proddatur", "Puducherry", "Pune", "Puri", "Purnea", "Raichur", "Raipur", "Rajahmundry", "Rajapalaiyam", "Rajkot", "Ramagundam", "Rampura", "Ranchi", "Ratlam", "Raurkela", "Rohtak", "Saharanpur", "Saidapur", "Saidpur", "Salem", "Samlaipadar", "Sangli", "Saugor", "Shahbazpur", "Shiliguri", "Shillong", "Shimla", "Shimoga", "Sikar", "Silchar", "Silvassa", "Sirsa", "Sonipat", "Srinagar", "Surat", "Tezpur", "Thanjavur", "Tharati Etawah", "Thiruvananthapuram", "Tiruchchirappalli", "Tirunelveli", "Tirupati", "Tiruvannamalai", "Tonk", "Tuticorin", "Udaipur", "Ujjain", "Vadodara", "Valparai", "Varanasi", "Vellore", "Vishakhapatnam", "Vizianagaram", "Warangal", "Jorapokhar", "Brajrajnagar", "Talcher"];


        // foreach ($skills as $skill) {
        //     $skills_data = new SkillsData();
        //     $skills_data->name = $skill;
        //     $skills_data->save();
        // }

        // foreach ($states as $state) {
        //     $states_data = new State();
        //     $states_data->name = $state;
        //     $states_data->save();
        // }

        // foreach ($countries as $country) {
        //     $countries_data = new Country();
        //     $countries_data->name = $country;
        //     $countries_data->save();
        // }

        // foreach ($states as $state) {
        //     $states_data = new State();
        //     $states_data->name = $state;
        //     $states_data->save();
        // }

        // foreach ($cities as $city) {
        //     $cities_data = new City();
        //     $cities_data->name = $city;
        //     $cities_data->save();
        // }

        // foreach ($sponsorship_packages as $sponsorship){
        //    $sponsorship_packages = new SponsorshipPackages();
        //    $sponsorship_packages->name = $sponsorship;
        //    $sponsorship_packages->save();
        // }

        // foreach ($companies as $company) {
        //     $company = new Company();
        //     $company->name = $company;
        //     $company->save();
        // }

        // foreach ($industries as $industry) {
        //     $company = new Industry();
        //     $company->name = $industry;
        //     $company->save();
        // }

        // foreach ($job_titles as $job_title) {
        //     $job = new JobTitle();
        //     $job->name = $job_title;
        //     $job->save();
        // }

        return response()->json([
            'status' => 200,
            'message' => 'API Working'
        ]);
    }

    //User-Registration 
    public function register(Request $request)
    {
        if ($request->step === '1') {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required | string | max:200',
                'last_name' => 'string | max:200',
                'email' => 'required | email | max: 255 | unique:users',
                'password' => 'required | min:8',
                'mobile_number' => 'required | min:10 | max:10 | unique:users',
                'company' => 'required',
                'designation' => 'required',
                'pincode' => 'required',
                'address' => 'required',
                'step' => 'required',
                'tnc' => 'required'
            ]);

            if ($validator->fails()) {

                $errors = $validator->errors();

                return response()->json([
                    'status' => 422,
                    'message' => 'Validation Error',
                    'error' => $errors,
                ]);
            }

            // Send OTP 
            $mobile_otp = rand(100000, 999999);
            $email_otp = rand(100000, 999999);

            // For Demo purpose 
            // $email_otp = '123456';
            // $mobile_otp = '123456';

            $email = $request->email;
            $mobile_number = $request->mobile_number;

            UserOtp::where('email', $email)->delete();

            UserOtp::create([
                'email' => $email,
                'email_otp' => $email_otp,
                'mobile' =>  $mobile_number,
                'mobile_otp'  => $mobile_otp
            ]);

            $this->smsService->sendSMS('+91' . $mobile_number, 'Your OTP is : ' . $mobile_otp);

            $email_message = 'Your OTP is : ' . $email_otp;

            $this->emailService->sendRegistrationEmail($email, 'Klout: OTP Verification', $email_message);

            return response()->json([
                'status' => 200,
                'message' => 'OTP Send to Mobile Number and Email.'
            ]);
        } elseif ($request->step === '2') {

            $email = $request->email;
            $mobile = $request->mobile_number;

            $mobile_verify = UserOtp::where('mobile', $mobile)->first();
            $email_verify = UserOtp::where('email', $email)->first();

            if (!empty($mobile_verify) && !empty($mobile_verify)) {


                if (($mobile_verify->mobile_otp !== trim($request->mobile_otp))) {
                    return response()->json([
                        'status' => 400,
                        'error' => 'mobile_otp',
                        'message' => 'Mobile OTP is Invalid.',
                    ]);
                }

                if (($email_verify->email_otp !== trim($request->email_otp))) {
                    return response()->json([
                        'status' => 400,
                        'error' => 'email_otp',
                        'message' => 'Email OTP is Invalid.',
                    ]);
                } else if (!empty($mobile_verify) && !empty($email_verify)) {

                    $user = User::create([
                        'uuid' => Uuid::uuid4()->toString(),
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => strtolower($request->email),
                        'password' => Hash::make($request->password),
                        'mobile_number' => $request->mobile_number,
                        'company' => $request->company,
                        'company_name' => !empty($request->company_name) ? $request->company_name : "",
                        'designation_name' => !empty($request->designation_name) ? $request->designation_name : "",
                        'designation' => $request->designation,
                        'pincode' => $request->pincode,
                        'address' => $request->address,
                        'tnc' => (!empty($request->tnc) && $request->tnc === "on")  ? "1" : "0",
                        'notifications' => (!empty($request->notifications) && $request->notifications === "on")  ? "1" : "0"
                    ]);

                    $registration_success_message = "Congratulations ! Your registration is Completed on Klout Club.";

                    $this->smsService->sendSMS('+91' . $mobile, $registration_success_message);

                    $this->emailService->sendRegistrationEmail($email, 'Klout : Registration Successfully', $registration_success_message);

                    $delete_otp_record = UserOtp::where('email', $email)->delete();

                    if ($delete_otp_record) {
                        return response()->json([
                            'status' => 200,
                            'message' => 'OTP Verified Successfully'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 401,
                        'message' => 'Something Went Wrong.Please try again.'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid OTP.Please try again.'
                ]);
            }
        }


        return response()->json([
            'status' => 200,
            'message' => 'Invalid paramters and try again.'
        ]);
    }

    //User-Login
    public function login(Request $request)
    {
        $validator = validator::make($request->all(), [
            'email' => 'required | max:200',
            'password' => 'required'
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'error' => $errors,
            ]);
        } else {

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {

                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid Credentials'
                ]);
            } else {

                $token = $user->createToken($user->email . '_Token')->plainTextToken;

                return response()->json([
                    'status' => 200,
                    'message' => 'Logged In Successfully',
                    'email' => 'Welcome to Kloud Club - ' . ucfirst($user->first_name),
                    'access_token_type' => 'Bearer',
                    'access_token' => $token,
                ]);
            }
        }
    }

    //Auth - Logout 
    public function logout()
    {
        $user = Auth::user();

        if ($user) {

            $user->tokens()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'You have successfully logged out.'
            ]);
        }

        return response()->json(['message' => 'User not authenticated.'], 401);
    }

    //Auth - Forgot Password Link 
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'Email is not registered.',
                'error' => $errors->first(),
            ]);
        } else {

            $email = $request->input('email');

            $user = User::where('email', $email)->first();

            if ($user) {

                $token = Str::random(64);

                $exist = PasswordReset::where('email', $email)->get();

                if (!empty($exist)) {
                    PasswordReset::where('email', $email)->delete();
                }

                PasswordReset::create([
                    'email' => $email,
                    'token' => $token,
                ]);

                // Send the password reset link via email
                $resetLink  = Config('app.front_end_url') . '/reset-password?email=' . $email . '&token=' . $token;

                $this->smsService->sendSMS('+91' . $user->mobile_number, 'Dear User, 
                Thank you for your request to change your password.Please click link to enter your new password :
             ' . $resetLink . '
             If you have not requested to change password, then ignore this message.
             
             Kind regards,
             Klout Club');

                Mail::to($email)->send(new ResetPasswordMail($resetLink));

                return response()->json(
                    [
                        'status' => '200',
                        'message' => 'Reset password link sent successfully.'
                    ]
                );
            } else {
                return response()->json(
                    [
                        'status' => '400',
                        'message' => 'Invlaid Email! Data not Found.'
                    ]
                );
            }
        }
    }

    //Reset Password 
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'Invalid Data.',
                'error' => $errors
            ]);
        } else {

            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');
            $confirm_password = $request->input('confirm_password');

            // Check if the token is valid for the given email
            $passwordReset = PasswordReset::where('email', $email)->first();

            if (!$passwordReset || $passwordReset->token !== $request->input('token')) {
                return response()->json([
                    'status' => 422,
                    'error' => 'Link Expired.Please try again.'
                ]);
            }

            if (trim($request->input('password')) !== trim($request->input('confirm_password'))) {
                return response()->json([
                    'status' => 404,
                    'error' => 'New password or Confirm Password are not matching.'
                ]);
            }

            // Find the user and update the password
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(
                    [
                        'status' => 404,
                        'error' => 'User not found.Please try reset password link again.'
                    ]
                );
            }

            $user->password = Hash::make($password);
            $user->save();

            // Delete the password reset entry from the table
            PasswordReset::where('email', $email)->delete();

            //send mail and sms
            $changed_password_success_message = "Congratulations ! Password Changed Successfully.";

            $this->emailService->sendChangedPasswordEmail($email, 'Klout : Password Changed', $changed_password_success_message);

            return response()->json(['status' => 200, 'message' => 'Password Reset Successfully']);
        }
    }

    //JobTitles 
    public function jobTitle()
    {
        $jobTitles = JobTitle::orderBy('name', 'asc')->get()->toArray();

        if ($jobTitles) {
            return response()->json([
                'status' => 200,
                'message' => 'All Job-Titles',
                'data' => $jobTitles
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data not Found',
                'data' => []
            ]);
        }
    }

    //Companies
    public function companies()
    {
        $companies = Company::orderBy('name', 'asc')->get()->toArray();

        if ($companies) {
            return response()->json([
                'status' => 200,
                'message' => 'All Companies',
                'data' => $companies
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data not Found',
                'data' => []
            ]);
        }
    }

    //Sponsorship Packages
    public function sponsorshipPackages()
    {
        $sponsorshipPackages = SponsorshipPackages::orderBy('name', 'asc')->get()->toArray();

        if ($sponsorshipPackages) {
            return response()->json([
                'status' => 200,
                'message' => 'All Sponsorship Packages',
                'data' => $sponsorshipPackages
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data not Found',
                'data' => []
            ]);
        }
    }

    //Industries
    public function industries()
    {
        $industries = Industry::orderBy('name', 'asc')->get()->toArray();

        if ($industries) {
            return response()->json([
                'status' => 200,
                'message' => 'All Industries',
                'data' => $industries
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data not Found',
                'data' => []
            ]);
        }
    }

    // Subscribe 
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {

            $error = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'User already subscribed.',
                'error' => ucwords($error->first())
            ]);
        } else {

            $user = Subscriber::where('email', $request->email)->first();

            if ($user) {
                response()->json([
                    'status' => 201,
                    'message' => 'User already subscribed.'
                ]);
            } else {
                Subscriber::create([
                    'email' => $request->input('semail'),
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Subscribed successfully.'
                ]);
            }
        }
    }

    // Unsubscribe
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        $subscriber = Subscriber::where('email', $request->input('email'))->get();

        if ($subscriber) {

            $subscriber = Subscriber::where('email', $request->input('email'))->first();
            $subscriber->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Unsubscribed Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'Email Not found.'
            ]);
        }
    }

    // Contact-Us
    public function contact_us(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required',
            'subject' => 'required',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {

            $error = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'Invalid Data.',
                'error' => $error
            ]);
        } else {

            ContactMessage::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Message submitted successfully'
            ]);
        }
    }

    // Website Settings
    public function website_settings(Request $request, $key)
    {
        // Update a specific website setting by key
        $request->validate([
            'value' => 'required',
        ]);

        $setting = WebsiteSetting::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $request->input('value')]);
            return response()->json(['message' => 'Setting updated successfully']);
        }

        return response()->json(['error' => 'Setting not found'], 404);
    }

    public function show_website_settings($key)
    {
        // Retrieve a specific website setting by key
        $setting = WebsiteSetting::where('key', $key)->first();

        return $setting
            ? response()->json($setting)
            : response()->json(['error' => 'Setting not found'], 404);
    }
    public function all_website_settings()
    {
        // Retrieve a specific website setting by key
        $settings = WebsiteSetting::all();

        return response()->json($settings);
    }
}
