<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 27.04.14
 * Time: 14:46
 */

namespace X\Data;

class Languages {
  public $lang2code = [
    'Abkhaz'=>'ab',
    'Afar'=>'aa',
    'Afrikaans'=>'af',
    'Akan'=>'ak',
    'Albanian'=>'sq',
    'Amharic'=>'am',
    'Arabic'=>'ar',
    'Aragonese'=>'an',
    'Armenian'=>'hy',
    'Assamese'=>'as',
    'Avaric'=>'av',
    'Avestan'=>'ae',
    'Aymara'=>'ay',
    'Azerbaijani'=>'az',
    'Bambara'=>'bm',
    'Bashkir'=>'ba',
    'Basque'=>'eu',
    'Belarusian'=>'be',
    'Bengali'=>'bn',
    'Bihari'=>'bh',
    'Bislama'=>'bi',
    'Bosnian'=>'bs',
    'Breton'=>'br',
    'Bulgarian'=>'bg',
    'Burmese'=>'my',
    'Catalan'=>'ca',
    'Chamorro'=>'ch',
    'Chechen'=>'ce',
    'Chichewa'=>'ny',
    'Chinese'=>'zh',
    'Chuvash'=>'cv',
    'Cornish'=>'kw',
    'Corsican'=>'co',
    'Cree'=>'cr',
    'Croatian'=>'hr',
    'Czech'=>'cs',
    'Danish'=>'da',
    'Divehi'=>'dv',
    'Dutch'=>'nl',
    'Dzongkha'=>'dz',
    'English'=>'en',
    'Esperanto'=>'eo',
    'Estonian'=>'et',
    'Ewe'=>'ee',
    'Faroese'=>'fo',
    'Fijian'=>'fj',
    'Finnish'=>'fi',
    'French'=>'fr',
    'Fula'=>'ff',
    'Galician'=>'gl',
    'Georgian'=>'ka',
    'German'=>'de',
    'Greek'=>'el',
    'Guaraní'=>'gn',
    'Gujarati'=>'gu',
    'Haitian'=>'ht',
    'Hausa'=>'ha',
    'Hebrew'=>'he',
    'Herero'=>'hz',
    'Hindi'=>'hi',
    'Hiri Motu'=>'ho',
    'Hungarian'=>'hu',
    'Interlingua'=>'ia',
    'Indonesian'=>'id',
    'Interlingue'=>'ie',
    'Irish'=>'ga',
    'Igbo'=>'ig',
    'Inupiaq'=>'ik',
    'Ido'=>'io',
    'Icelandic'=>'is',
    'Italian'=>'it',
    'Inuktitut'=>'iu',
    'Japanese'=>'ja',
    'Javanese'=>'jv',
    'Kalaallisut'=>'kl',
    'Kannada'=>'kn',
    'Kanuri'=>'kr',
    'Kashmiri'=>'ks',
    'Kazakh'=>'kk',
    'Khmer'=>'km',
    'Kikuyu'=>'ki',
    'Kinyarwanda'=>'rw',
    'Kyrgyz'=>'ky',
    'Komi'=>'kv',
    'Kongo'=>'kg',
    'Korean'=>'ko',
    'Kurdish'=>'ku',
    'Kwanyama'=>'kj',
    'Latin'=>'la',
    'Luxembourgish'=>'lb',
    'Ganda'=>'lg',
    'Limburgish'=>'li',
    'Lingala'=>'ln',
    'Lao'=>'lo',
    'Lithuanian'=>'lt',
    'Luba-Katanga'=>'lu',
    'Latvian'=>'lv',
    'Manx'=>'gv',
    'Macedonian'=>'mk',
    'Malagasy'=>'mg',
    'Malay'=>'ms',
    'Malayalam'=>'ml',
    'Maltese'=>'mt',
    'Māori'=>'mi',
    'Marathi'=>'mr',
    'Marshallese'=>'mh',
    'Mongolian'=>'mn',
    'Nauru'=>'na',
    'Navajo'=>'nv',
    'Norwegian Bokmål'=>'nb',
    'Northern Ndebele'=>'nd',
    'Nepali'=>'ne',
    'Ndonga'=>'ng',
    'Norwegian Nynorsk'=>'nn',
    'Norwegian'=>'no',
    'Nuosu'=>'ii',
    'Southern Ndebele'=>'nr',
    'Occitan'=>'oc',
    'Ojibwe'=>'oj',
    'Old Church Slavonic'=>'cu',
    'Oromo'=>'om',
    'Oriya'=>'or',
    'Ossetian'=>'os',
    'Panjabi'=>'pa',
    'Pali'=>'pi',
    'Persian'=>'fa',
    'Farsi'=>'fa',
    'Polish'=>'pl',
    'Pashto'=>'ps',
    'Portuguese'=>'pt',
    'Quechua'=>'qu',
    'Romansh'=>'rm',
    'Kirundi'=>'rn',
    'Romanian'=>'ro',
    'Russian'=>'ru',
    'Sanskrit'=>'sa',
    'Sardinian'=>'sc',
    'Sindhi'=>'sd',
    'Northern Sami'=>'se',
    'Samoan'=>'sm',
    'Sango'=>'sg',
    'Serbian'=>'sr',
    'Scottish Gaelic'=>'gd',
    'Shona'=>'sn',
    'Sinhala'=>'si',
    'Slovak'=>'sk',
    'Slovene'=>'sl',
    'Somali'=>'so',
    'Southern Sotho'=>'st',
    'Spanish'=>'es',
    'Sundanese'=>'su',
    'Swahili'=>'sw',
    'Swati'=>'ss',
    'Swedish'=>'sv',
    'Tamil'=>'ta',
    'Telugu'=>'te',
    'Tajik'=>'tg',
    'Thai'=>'th',
    'Tigrinya'=>'ti',
    'Tibetan Standard'=>'bo',
    'Turkmen'=>'tk',
    'Tagalog'=>'tl',
    'Tswana'=>'tn',
    'Tonga'=>'to',
    'Turkish'=>'tr',
    'Tsonga'=>'ts',
    'Tatar'=>'tt',
    'Twi'=>'tw',
    'Tahitian'=>'ty',
    'Uyghur'=>'ug',
    'Ukrainian'=>'uk',
    'Urdu'=>'ur',
    'Uzbek'=>'uz',
    'Venda'=>'ve',
    'Vietnamese'=>'vi',
    'Volapük'=>'vo',
    'Walloon'=>'wa',
    'Welsh'=>'cy',
    'Wolof'=>'wo',
    'Western Frisian'=>'fy',
    'Xhosa'=>'xh',
    'Yiddish'=>'yi',
    'Yoruba'=>'yo',
    'Zhuang'=>'za',
    'Zulu'=>'zu',
  ];

  public $code2lang=[
    'ab'=>'Abkhaz',
    'aa'=>'Afar',
    'af'=>'Afrikaans',
    'ak'=>'Akan',
    'sq'=>'Albanian',
    'am'=>'Amharic',
    'ar'=>'Arabic',
    'an'=>'Aragonese',
    'hy'=>'Armenian',
    'as'=>'Assamese',
    'av'=>'Avaric',
    'ae'=>'Avestan',
    'ay'=>'Aymara',
    'az'=>'Azerbaijani',
    'bm'=>'Bambara',
    'ba'=>'Bashkir',
    'eu'=>'Basque',
    'be'=>'Belarusian',
    'bn'=>'Bengali',
    'bh'=>'Bihari',
    'bi'=>'Bislama',
    'bs'=>'Bosnian',
    'br'=>'Breton',
    'bg'=>'Bulgarian',
    'my'=>'Burmese',
    'ca'=>'Catalan',
    'ch'=>'Chamorro',
    'ce'=>'Chechen',
    'ny'=>'Chichewa',
    'zh'=>'Chinese',
    'cv'=>'Chuvash',
    'kw'=>'Cornish',
    'co'=>'Corsican',
    'cr'=>'Cree',
    'hr'=>'Croatian',
    'cs'=>'Czech',
    'da'=>'Danish',
    'dv'=>'Divehi',
    'nl'=>'Dutch',
    'dz'=>'Dzongkha',
    'en'=>'English',
    'eo'=>'Esperanto',
    'et'=>'Estonian',
    'ee'=>'Ewe',
    'fo'=>'Faroese',
    'fj'=>'Fijian',
    'fi'=>'Finnish',
    'fr'=>'French',
    'ff'=>'Fula',
    'gl'=>'Galician',
    'ka'=>'Georgian',
    'de'=>'German',
    'el'=>'Greek',
    'gn'=>'Guaraní',
    'gu'=>'Gujarati',
    'ht'=>'Haitian',
    'ha'=>'Hausa',
    'he'=>'Hebrew',
    'hz'=>'Herero',
    'hi'=>'Hindi',
    'ho'=>'Hiri Motu',
    'hu'=>'Hungarian',
    'ia'=>'Interlingua',
    'id'=>'Indonesian',
    'ie'=>'Interlingue',
    'ga'=>'Irish',
    'ig'=>'Igbo',
    'ik'=>'Inupiaq',
    'io'=>'Ido',
    'is'=>'Icelandic',
    'it'=>'Italian',
    'iu'=>'Inuktitut',
    'ja'=>'Japanese',
    'jv'=>'Javanese',
    'kl'=>'Kalaallisut',
    'kn'=>'Kannada',
    'kr'=>'Kanuri',
    'ks'=>'Kashmiri',
    'kk'=>'Kazakh',
    'km'=>'Khmer',
    'ki'=>'Kikuyu',
    'rw'=>'Kinyarwanda',
    'ky'=>'Kyrgyz',
    'kv'=>'Komi',
    'kg'=>'Kongo',
    'ko'=>'Korean',
    'ku'=>'Kurdish',
    'kj'=>'Kwanyama',
    'la'=>'Latin',
    'lb'=>'Luxembourgish',
    'lg'=>'Ganda',
    'li'=>'Limburgish',
    'ln'=>'Lingala',
    'lo'=>'Lao',
    'lt'=>'Lithuanian',
    'lu'=>'Luba-Katanga',
    'lv'=>'Latvian',
    'gv'=>'Manx',
    'mk'=>'Macedonian',
    'mg'=>'Malagasy',
    'ms'=>'Malay',
    'ml'=>'Malayalam',
    'mt'=>'Maltese',
    'mi'=>'Māori',
    'mr'=>'Marathi',
    'mh'=>'Marshallese',
    'mn'=>'Mongolian',
    'na'=>'Nauru',
    'nv'=>'Navajo',
    'nb'=>'Norwegian Bokmål',
    'nd'=>'Northern Ndebele',
    'ne'=>'Nepali',
    'ng'=>'Ndonga',
    'nn'=>'Norwegian Nynorsk',
    'no'=>'Norwegian',
    'ii'=>'Nuosu',
    'nr'=>'Southern Ndebele',
    'oc'=>'Occitan',
    'oj'=>'Ojibwe',
    'cu'=>'Old Church Slavonic',
    'om'=>'Oromo',
    'or'=>'Oriya',
    'os'=>'Ossetian',
    'pa'=>'Panjabi',
    'pi'=>'Pali',
    'fa'=>'Farsi',
    'pl'=>'Polish',
    'ps'=>'Pashto',
    'pt'=>'Portuguese',
    'qu'=>'Quechua',
    'rm'=>'Romansh',
    'rn'=>'Kirundi',
    'ro'=>'Romanian',
    'ru'=>'Russian',
    'sa'=>'Sanskrit',
    'sc'=>'Sardinian',
    'sd'=>'Sindhi',
    'se'=>'Northern Sami',
    'sm'=>'Samoan',
    'sg'=>'Sango',
    'sr'=>'Serbian',
    'gd'=>'Scottish Gaelic',
    'sn'=>'Shona',
    'si'=>'Sinhala',
    'sk'=>'Slovak',
    'sl'=>'Slovene',
    'so'=>'Somali',
    'st'=>'Southern Sotho',
    'es'=>'Spanish',
    'su'=>'Sundanese',
    'sw'=>'Swahili',
    'ss'=>'Swati',
    'sv'=>'Swedish',
    'ta'=>'Tamil',
    'te'=>'Telugu',
    'tg'=>'Tajik',
    'th'=>'Thai',
    'ti'=>'Tigrinya',
    'bo'=>'Tibetan Standard',
    'tk'=>'Turkmen',
    'tl'=>'Tagalog',
    'tn'=>'Tswana',
    'to'=>'Tonga',
    'tr'=>'Turkish',
    'ts'=>'Tsonga',
    'tt'=>'Tatar',
    'tw'=>'Twi',
    'ty'=>'Tahitian',
    'ug'=>'Uyghur',
    'uk'=>'Ukrainian',
    'ur'=>'Urdu',
    'uz'=>'Uzbek',
    've'=>'Venda',
    'vi'=>'Vietnamese',
    'vo'=>'Volapük',
    'wa'=>'Walloon',
    'cy'=>'Welsh',
    'wo'=>'Wolof',
    'fy'=>'Western Frisian',
    'xh'=>'Xhosa',
    'yi'=>'Yiddish',
    'yo'=>'Yoruba',
    'za'=>'Zhuang',
    'zu'=>'Zulu',
  ];

  public $code2local=[ //unicode escaped
    'ab'=>'%u0430%u04A7%u0441%u0443%u0430%20%u0431%u044B%u0437%u0448%u04D9%u0430%2C%20%u0430%u04A7%u0441%u0448%u04D9%u0430',
    'aa'=>'Afaraf',
    'af'=>'Afrikaans',
    'ak'=>'Akan',
    'sq'=>'gjuha%20shqipe',
    'am'=>'%u12A0%u121B%u122D%u129B',
    'ar'=>'%0A%u0627%u0644%u0639%u0631%u0628%u064A%u0629%0A',
    'an'=>'aragon%E9s',
    'hy'=>'%u0540%u0561%u0575%u0565%u0580%u0565%u0576',
    'as'=>'%u0985%u09B8%u09AE%u09C0%u09AF%u09BC%u09BE',
    'av'=>'%u0430%u0432%u0430%u0440%20%u043C%u0430%u0446%u04C0%2C%20%u043C%u0430%u0433%u04C0%u0430%u0440%u0443%u043B%20%u043C%u0430%u0446%u04C0',
    'ae'=>'avesta',
    'ay'=>'aymar%20aru',
    'az'=>'az%u0259rbaycan%20dili',
    'bm'=>'bamanankan',
    'ba'=>'%u0431%u0430%u0448%u04A1%u043E%u0440%u0442%20%u0442%u0435%u043B%u0435',
    'eu'=>'euskara%2C%20euskera',
    'be'=>'%u0431%u0435%u043B%u0430%u0440%u0443%u0441%u043A%u0430%u044F%20%u043C%u043E%u0432%u0430',
    'bn'=>'%u09AC%u09BE%u0982%u09B2%u09BE',
    'bh'=>'%u092D%u094B%u091C%u092A%u0941%u0930%u0940',
    'bi'=>'Bislama',
    'bs'=>'bosanski%20jezik',
    'br'=>'brezhoneg',
    'bg'=>'%u0431%u044A%u043B%u0433%u0430%u0440%u0441%u043A%u0438%20%u0435%u0437%u0438%u043A',
    'my'=>'%u1017%u1019%u102C%u1005%u102C',
    'ca'=>'catal%E0%2C%20valenci%E0',
    'ch'=>'Chamoru',
    'ce'=>'%u043D%u043E%u0445%u0447%u0438%u0439%u043D%20%u043C%u043E%u0442%u0442',
    'ny'=>'chiChe%u0175a%2C%20chinyanja',
    'zh'=>'%u4E2D%u6587%20%28Zh%u014Dngw%E9n%29%2C%20%u6C49%u8BED%2C%20%u6F22%u8A9E',
    'cv'=>'%u0447%u04D1%u0432%u0430%u0448%20%u0447%u04D7%u043B%u0445%u0438',
    'kw'=>'Kernewek',
    'co'=>'corsu%2C%20lingua%20corsa',
    'cr'=>'%u14C0%u1426%u1403%u152D%u140D%u140F%u1423',
    'hr'=>'hrvatski%20jezik',
    'cs'=>'%u010De%u0161tina%2C%20%u010Desk%FD%20jazyk',
    'da'=>'dansk',
    'dv'=>'%0A%u078B%u07A8%u0788%u07AC%u0780%u07A8%0A',
    'nl'=>'Nederlands%2C%20Vlaams',
    'dz'=>'%u0F62%u0FAB%u0F7C%u0F44%u0F0B%u0F41',
    'en'=>'English',
    'eo'=>'Esperanto',
    'et'=>'eesti%2C%20eesti%20keel',
    'ee'=>'E%u028Begbe',
    'fo'=>'f%F8royskt',
    'fj'=>'vosa%20Vakaviti',
    'fi'=>'suomi%2C%20suomen%20kieli',
    'fr'=>'fran%E7ais%2C%20langue%20fran%E7aise',
    'ff'=>'Fulfulde%2C%20Pulaar%2C%20Pular',
    'gl'=>'galego',
    'ka'=>'%u10E5%u10D0%u10E0%u10D7%u10E3%u10DA%u10D8',
    'de'=>'Deutsch',
    'el'=>'%u03B5%u03BB%u03BB%u03B7%u03BD%u03B9%u03BA%u03AC',
    'gn'=>'Ava%F1e%27%u1EBD',
    'gu'=>'%u0A97%u0AC1%u0A9C%u0AB0%u0ABE%u0AA4%u0AC0',
    'ht'=>'Krey%F2l%20ayisyen',
    'ha'=>'%0A%28Hausa%29%20%u0647%u064E%u0648%u064F%u0633%u064E%0A',
    'he'=>'%0A%u05E2%u05D1%u05E8%u05D9%u05EA%0A',
    'hz'=>'Otjiherero',
    'hi'=>'%u0939%u093F%u0928%u094D%u0926%u0940%2C%20%u0939%u093F%u0902%u0926%u0940',
    'ho'=>'Hiri%20Motu',
    'hu'=>'magyar',
    'ia'=>'Interlingua',
    'id'=>'Bahasa%20Indonesia',
    'ie'=>'Originally%20called%20Occidental%3B%20then%20Interlingue%20after%20WWII',
    'ga'=>'Gaeilge',
    'ig'=>'As%u1EE5s%u1EE5%20Igbo',
    'ik'=>'I%F1upiaq%2C%20I%F1upiatun',
    'io'=>'Ido',
    'is'=>'%CDslenska',
    'it'=>'italiano',
    'iu'=>'%u1403%u14C4%u1483%u144E%u1450%u1466',
    'ja'=>'%u65E5%u672C%u8A9E%20%28%u306B%u307B%u3093%u3054%29',
    'jv'=>'basa%20Jawa',
    'kl'=>'kalaallisut%2C%20kalaallit%20oqaasii',
    'kn'=>'%u0C95%u0CA8%u0CCD%u0CA8%u0CA1',
    'kr'=>'Kanuri',
    'ks'=>'%u0915%u0936%u094D%u092E%u0940%u0930%u0940%2C%20%u0643%u0634%u0645%u064A%u0631%u064A%u200E',
    'kk'=>'%u049B%u0430%u0437%u0430%u049B%20%u0442%u0456%u043B%u0456',
    'km'=>'%u1781%u17D2%u1798%u17C2%u179A%2C%20%u1781%u17C1%u1798%u179A%u1797%u17B6%u179F%u17B6%2C%20%u1797%u17B6%u179F%u17B6%u1781%u17D2%u1798%u17C2%u179A',
    'ki'=>'G%u0129k%u0169y%u0169',
    'rw'=>'Ikinyarwanda',
    'ky'=>'%u041A%u044B%u0440%u0433%u044B%u0437%u0447%u0430%2C%20%u041A%u044B%u0440%u0433%u044B%u0437%20%u0442%u0438%u043B%u0438',
    'kv'=>'%u043A%u043E%u043C%u0438%20%u043A%u044B%u0432',
    'kg'=>'Kikongo',
    'ko'=>'%uD55C%uAD6D%uC5B4%2C%20%uC870%uC120%uC5B4',
    'ku'=>'Kurd%EE%2C%20%u0643%u0648%u0631%u062F%u06CC%u200E',
    'kj'=>'Kuanyama',
    'la'=>'latine%2C%20lingua%20latina',
    'lb'=>'L%EBtzebuergesch',
    'lg'=>'Luganda',
    'li'=>'Limburgs',
    'ln'=>'Ling%E1la',
    'lo'=>'%u0E9E%u0EB2%u0EAA%u0EB2%u0EA5%u0EB2%u0EA7',
    'lt'=>'lietuvi%u0173%20kalba',
    'lu'=>'Tshiluba',
    'lv'=>'latvie%u0161u%20valoda',
    'gv'=>'Gaelg%2C%20Gailck',
    'mk'=>'%u043C%u0430%u043A%u0435%u0434%u043E%u043D%u0441%u043A%u0438%20%u0458%u0430%u0437%u0438%u043A',
    'mg'=>'fiteny%20malagasy',
    'ms'=>'bahasa%20Melayu%2C%20%u0628%u0647%u0627%u0633%20%u0645%u0644%u0627%u064A%u0648%u200E',
    'ml'=>'%u0D2E%u0D32%u0D2F%u0D3E%u0D33%u0D02',
    'mt'=>'Malti',
    'mi'=>'te%20reo%20M%u0101ori',
    'mr'=>'%u092E%u0930%u093E%u0920%u0940',
    'mh'=>'Kajin%20M%u0327aje%u013C',
    'mn'=>'%u043C%u043E%u043D%u0433%u043E%u043B',
    'na'=>'Ekakair%u0169%20Naoero',
    'nv'=>'Din%E9%20bizaad%2C%20Din%E9k%u02BCeh%u01F0%ED',
    'nb'=>'Norsk%20bokm%E5l',
    'nd'=>'isiNdebele',
    'ne'=>'%u0928%u0947%u092A%u093E%u0932%u0940',
    'ng'=>'Owambo',
    'nn'=>'Norsk%20nynorsk',
    'no'=>'Norsk',
    'ii'=>'%uA188%uA320%uA4BF%20Nuosuhxop',
    'nr'=>'isiNdebele',
    'oc'=>'occitan%2C%20lenga%20d%27%F2c',
    'oj'=>'%u140A%u14C2%u1511%u14C8%u142F%u14A7%u140E%u14D0',
    'cu'=>'%u0469%u0437%u044B%u043A%u044A%20%u0441%u043B%u043E%u0432%u0463%u043D%u044C%u0441%u043A%u044A',
    'om'=>'Afaan%20Oromoo',
    'or'=>'%u0B13%u0B21%u0B3C%u0B3F%u0B06',
    'os'=>'%u0438%u0440%u043E%u043D%20%E6%u0432%u0437%u0430%u0433',
    'pa'=>'%u0A2A%u0A70%u0A1C%u0A3E%u0A2C%u0A40%2C%20%u067E%u0646%u062C%u0627%u0628%u06CC%u200E',
    'pi'=>'%u092A%u093E%u0934%u093F',
    'fa'=>'%0A%u0641%u0627%u0631%u0633%u06CC%0A',
    'pl'=>'j%u0119zyk%20polski%2C%20polszczyzna',
    'ps'=>'%0A%u067E%u069A%u062A%u0648%0A',
    'pt'=>'portugu%EAs',
    'qu'=>'Runa%20Simi%2C%20Kichwa',
    'rm'=>'rumantsch%20grischun',
    'rn'=>'Ikirundi',
    'ro'=>'limba%20rom%E2n%u0103',
    'ru'=>'%u0440%u0443%u0441%u0441%u043A%u0438%u0439%20%u044F%u0437%u044B%u043A',
    'sa'=>'%u0938%u0902%u0938%u094D%u0915%u0943%u0924%u092E%u094D',
    'sc'=>'sardu',
    'sd'=>'%u0938%u093F%u0928%u094D%u0927%u0940%2C%20%u0633%u0646%u068C%u064A%u060C%20%u0633%u0646%u062F%u06BE%u06CC%u200E',
    'se'=>'Davvis%E1megiella',
    'sm'=>'gagana%20fa%27a%20Samoa',
    'sg'=>'y%E2ng%E2%20t%EE%20s%E4ng%F6',
    'sr'=>'%u0441%u0440%u043F%u0441%u043A%u0438%20%u0458%u0435%u0437%u0438%u043A',
    'gd'=>'G%E0idhlig',
    'sn'=>'chiShona',
    'si'=>'%u0DC3%u0DD2%u0D82%u0DC4%u0DBD',
    'sk'=>'sloven%u010Dina%2C%20slovensk%FD%20jazyk',
    'sl'=>'slovenski%20jezik%2C%20sloven%u0161%u010Dina',
    'so'=>'Soomaaliga%2C%20af%20Soomaali',
    'st'=>'Sesotho',
    'es'=>'espa%F1ol%2C%20castellano',
    'su'=>'Basa%20Sunda',
    'sw'=>'Kiswahili',
    'ss'=>'SiSwati',
    'sv'=>'Svenska',
    'ta'=>'%u0BA4%u0BAE%u0BBF%u0BB4%u0BCD',
    'te'=>'%u0C24%u0C46%u0C32%u0C41%u0C17%u0C41',
    'tg'=>'%u0442%u043E%u04B7%u0438%u043A%u04E3%2C%20to%u011Fik%u012B%2C%20%u062A%u0627%u062C%u06CC%u06A9%u06CC%u200E',
    'th'=>'%u0E44%u0E17%u0E22',
    'ti'=>'%u1275%u130D%u122D%u129B',
    'bo'=>'%u0F56%u0F7C%u0F51%u0F0B%u0F61%u0F72%u0F42',
    'tk'=>'T%FCrkmen%2C%20%u0422%u04AF%u0440%u043A%u043C%u0435%u043D',
    'tl'=>'Wikang%20Tagalog%2C%20%u170F%u1712%u1703%u1705%u1714%20%u1706%u1704%u170E%u1713%u1704%u1714',
    'tn'=>'Setswana',
    'to'=>'faka%20Tonga',
    'tr'=>'T%FCrk%E7e',
    'ts'=>'Xitsonga',
    'tt'=>'%u0442%u0430%u0442%u0430%u0440%20%u0442%u0435%u043B%u0435%2C%20tatar%20tele',
    'tw'=>'Twi',
    'ty'=>'Reo%20Tahiti',
    'ug'=>'Uy%u01A3urq%u0259%2C%20%u0626%u06C7%u064A%u063A%u06C7%u0631%u0686%u06D5%u200E',
    'uk'=>'%u0443%u043A%u0440%u0430%u0457%u043D%u0441%u044C%u043A%u0430%20%u043C%u043E%u0432%u0430',
    'ur'=>'%0A%u0627%u0631%u062F%u0648%0A',
    'uz'=>'O%u2018zbek%2C%20%u040E%u0437%u0431%u0435%u043A%2C%20%u0623%u06C7%u0632%u0628%u06D0%u0643%u200E',
    've'=>'Tshiven%u1E13a',
    'vi'=>'Ti%u1EBFng%20Vi%u1EC7t',
    'vo'=>'Volap%FCk',
    'wa'=>'walon',
    'cy'=>'Cymraeg',
    'wo'=>'Wollof',
    'fy'=>'Frysk',
    'xh'=>'isiXhosa',
    'yi'=>'%0A%u05D9%u05D9%u05B4%u05D3%u05D9%u05E9%0A',
    'yo'=>'Yor%F9b%E1',
    'za'=>'Sa%u026F%20cue%u014B%u0185%2C%20Saw%20cuengh',
    'zu'=>'isiZulu'
]

  const ABKHAZ='ab';
  const AFAR='aa';
  const AFRIKAANS='af';
  const AKAN='ak';
  const ALBANIAN='sq';
  const AMHARIC='am';
  const ARABIC='ar';
  const ARAGONESE='an';
  const ARMENIAN='hy';
  const ASSAMESE='as';
  const AVARIC='av';
  const AVESTAN='ae';
  const AYMARA='ay';
  const AZERBAIJANI='az';
  const BAMBARA='bm';
  const BASHKIR='ba';
  const BASQUE='eu';
  const BELARUSIAN='be';
  const BENGALI='bn';
  const BIHARI='bh';
  const BISLAMA='bi';
  const BOSNIAN='bs';
  const BRETON='br';
  const BULGARIAN='bg';
  const BURMESE='my';
  const CATALAN='ca';
  const CHAMORRO='ch';
  const CHECHEN='ce';
  const CHICHEWA='ny';
  const CHINESE='zh';
  const CHUVASH='cv';
  const CORNISH='kw';
  const CORSICAN='co';
  const CREE='cr';
  const CROATIAN='hr';
  const CZECH='cs';
  const DANISH='da';
  const DIVEHI='dv';
  const DUTCH='nl';
  const DZONGKHA='dz';
  const ENGLISH='en';
  const ESPERANTO='eo';
  const ESTONIAN='et';
  const EWE='ee';
  const FAROESE='fo';
  const FIJIAN='fj';
  const FINNISH='fi';
  const FRENCH='fr';
  const FULA='ff';
  const GALICIAN='gl';
  const GEORGIAN='ka';
  const GERMAN='de';
  const GREEK='el';
  const GUARANÍ='gn';
  const GUJARATI='gu';
  const HAITIAN='ht';
  const HAUSA='ha';
  const HEBREW='he';
  const HERERO='hz';
  const HINDI='hi';
  const HIRI_MOTU='ho';
  const HUNGARIAN='hu';
  const INTERLINGUA='ia';
  const INDONESIAN='id';
  const INTERLINGUE='ie';
  const IRISH='ga';
  const IGBO='ig';
  const INUPIAQ='ik';
  const IDO='io';
  const ICELANDIC='is';
  const ITALIAN='it';
  const INUKTITUT='iu';
  const JAPANESE='ja';
  const JAVANESE='jv';
  const KALAALLISUT='kl';
  const KANNADA='kn';
  const KANURI='kr';
  const KASHMIRI='ks';
  const KAZAKH='kk';
  const KHMER='km';
  const KIKUYU='ki';
  const KINYARWANDA='rw';
  const KYRGYZ='ky';
  const KOMI='kv';
  const KONGO='kg';
  const KOREAN='ko';
  const KURDISH='ku';
  const KWANYAMA='kj';
  const LATIN='la';
  const LUXEMBOURGISH='lb';
  const GANDA='lg';
  const LIMBURGISH='li';
  const LINGALA='ln';
  const LAO='lo';
  const LITHUANIAN='lt';
  const LUBA_KATANGA='lu';
  const LATVIAN='lv';
  const MANX='gv';
  const MACEDONIAN='mk';
  const MALAGASY='mg';
  const MALAY='ms';
  const MALAYALAM='ml';
  const MALTESE='mt';
  const MĀORI='mi';
  const MARATHI='mr';
  const MARSHALLESE='mh';
  const MONGOLIAN='mn';
  const NAURU='na';
  const NAVAJO='nv';
  const NORWEGIAN_BOKMÅL='nb';
  const NORTHERN_NDEBELE='nd';
  const NEPALI='ne';
  const NDONGA='ng';
  const NORWEGIAN_NYNORSK='nn';
  const NORWEGIAN='no';
  const NUOSU='ii';
  const SOUTHERN_NDEBELE='nr';
  const OCCITAN='oc';
  const OJIBWE='oj';
  const OLD_CHURCH_SLAVONIC='cu';
  const OROMO='om';
  const ORIYA='or';
  const OSSETIAN='os';
  const PANJABI='pa';
  const PALI='pi';
  const PERSIAN='fa';
  const FARSI='fa';
  const POLISH='pl';
  const PASHTO='ps';
  const PORTUGUESE='pt';
  const QUECHUA='qu';
  const ROMANSH='rm';
  const KIRUNDI='rn';
  const ROMANIAN='ro';
  const RUSSIAN='ru';
  const SANSKRIT='sa';
  const SARDINIAN='sc';
  const SINDHI='sd';
  const NORTHERN_SAMI='se';
  const SAMOAN='sm';
  const SANGO='sg';
  const SERBIAN='sr';
  const SCOTTISH_GAELIC='gd';
  const SHONA='sn';
  const SINHALA='si';
  const SLOVAK='sk';
  const SLOVENE='sl';
  const SOMALI='so';
  const SOUTHERN_SOTHO='st';
  const SPANISH='es';
  const SUNDANESE='su';
  const SWAHILI='sw';
  const SWATI='ss';
  const SWEDISH='sv';
  const TAMIL='ta';
  const TELUGU='te';
  const TAJIK='tg';
  const THAI='th';
  const TIGRINYA='ti';
  const TIBETAN_STANDARD='bo';
  const TURKMEN='tk';
  const TAGALOG='tl';
  const TSWANA='tn';
  const TONGA='to';
  const TURKISH='tr';
  const TSONGA='ts';
  const TATAR='tt';
  const TWI='tw';
  const TAHITIAN='ty';
  const UYGHUR='ug';
  const UKRAINIAN='uk';
  const URDU='ur';
  const UZBEK='uz';
  const VENDA='ve';
  const VIETNAMESE='vi';
  const VOLAPÜK='vo';
  const WALLOON='wa';
  const WELSH='cy';
  const WOLOF='wo';
  const WESTERN_FRISIAN='fy';
  const XHOSA='xh';
  const YIDDISH='yi';
  const YORUBA='yo';
  const ZHUANG='za';
  const ZULU='zu';
} 