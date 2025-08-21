<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertProveedor extends Migration
{
    public function up()
    {
        // Verificar existencia de la tabla
        if (!$this->db->tableExists('Proveedor')) {
            throw new \RuntimeException('La tabla Proveedor no existe');
        }

        // Datos de proveedores 
        $proveedores = [
            [
                'Nombre' => 'ABASTECEDORA MAXIO SA DE CV',
                'Nombre_Comercial' => 'ABASTECEDORA MAXIO SA DE CV',
                'RFC' => 'AMA0512139J5',
                'Banco' => 'HSBC',
                'Cuenta' => '4029778735',
                'Clabe' => '021580040297787352',
                'Tel_Contacto' => '9992989750',
                'Nombre_Contacto' => 'MIGUEL YAM',
                'Servicio' => 'PINTURA, TABLAROCA'
            ],
            [
                'Nombre' => 'ABELARDO SIERRA CALDERON',
                'Nombre_Comercial' => 'ABELARDO SIERRA CALDERON',
                'RFC' => 'SICA770201LRA',
                'Banco' => 'BANREGIO',
                'Cuenta' => '251009920016',
                'Clabe' => '058910000001879217',
                'Tel_Contacto' => '9992566074',
                'Nombre_Contacto' => 'ABELARDO SIERRA',
                'Servicio' => 'QUIMICOS LIMPIEZA Y JARCERIA'
            ],
            [
                'Nombre' => 'AGROCAMPO PENINSULAR SA DE CV',
                'Nombre_Comercial' => 'AGROCAMPO PENINSULAR SA DE CV',
                'RFC' => 'APE990122AGA',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0103621529',
                'Clabe' => '012910001036215292',
                'Tel_Contacto' => '9993474779',
                'Nombre_Contacto' => 'JORGE AKE',
                'Servicio' => 'FERTILIZANTES, CULTIVOS, EQUIPO MECANICO'
            ],
            [
                'Nombre' => 'ANDAMIOS Y MAQUINARIA LA PIRAMIDE SA DE CV',
                'Nombre_Comercial' => 'ANDAMIOS Y MAQUINARIA LA PIRAMIDE SA DE CV',
                'RFC' => 'AMP920224N29',
                'Banco' => 'HSBC',
                'Cuenta' => '4023853849',
                'Clabe' => '021910040238538499',
                'Tel_Contacto' => '9991277029',
                'Nombre_Contacto' => 'GREGORY VAZQUEZ',
                'Servicio' => 'RENTA ANDAMIOS'
            ],
            [
                'Nombre' => 'ARMADORA Y ENSAMBLE DE ALUMINIOS-ANEROL´S',
                'Nombre_Comercial' => 'ARMADORA Y ENSAMBLE DE ALUMINIOS-ANEROL´S',
                'RFC' => 'NOCM970323T97',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => null,
                'Clabe' => '012910004795650946',
                'Tel_Contacto' => '9993374693',
                'Nombre_Contacto' => 'ANGEL HERRERA',
                'Servicio' => 'SERVICIOS DE CANCELERIA Y ALUMINIO'
            ],
            [
                'Nombre' => 'ARTURO HiPOLiTO MOLiNA',
                'Nombre_Comercial' => 'ARTURO HiPOLiTO MOLiNA',
                'RFC' => 'HIMA770824NH3',
                'Banco' => 'BANORTE',
                'Cuenta' => '4246540',
                'Clabe' => '072910008142465400',
                'Tel_Contacto' => '9991144759',
                'Nombre_Contacto' => 'ARTURO HiPOLiTO MOLiNA',
                'Servicio' => 'COMPRA DE TORTILLA'
            ],
            [
                'Nombre' => 'AUTOMOTRIZ MONTECRISTO SA DE CV',
                'Nombre_Comercial' => 'AUTOMOTRIZ MONTECRISTO SA DE CV',
                'RFC' => 'AMO920408HA3',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0106199488',
                'Clabe' => '012910001061994889',
                'Tel_Contacto' => '9991578856',
                'Nombre_Contacto' => 'BRIAN HERRERA',
                'Servicio' => 'ASESOR DE CHEVROLET, CITAS, SERVICIOS ETC.'
            ],
            [
                'Nombre' => 'AUTOTRANSPORTES PROGRESO',
                'Nombre_Comercial' => 'AUTOTRANSPORTES PROGRESO',
                'RFC' => 'APM860824IF1',
                'Banco' => 'BANAMEX',
                'Cuenta' => '125150975',
                'Clabe' => '002910701251509750',
                'Tel_Contacto' => '9999063883',
                'Nombre_Contacto' => 'LiC GLADYS',
                'Servicio' => 'RENTA CAMION'
            ],
            [
                'Nombre' => 'AVAN TRADE FOOD SERViCE S DE RL CV',
                'Nombre_Comercial' => 'AVAN TRADE FOOD SERViCE S DE RL CV',
                'RFC' => 'AFS120320GGA',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65508088172',
                'Clabe' => '014910655080881725',
                'Tel_Contacto' => '9995710804',
                'Nombre_Contacto' => 'LiC GABRiELA SANTOYYO',
                'Servicio' => 'ABARROTES'
            ],
            [
                'Nombre' => 'BODEGA ELECTRONICA XAZE SA DE CV',
                'Nombre_Comercial' => 'BODEGA ELECTRONICA XAZE SA DE CV',
                'RFC' => 'BEX0303015P1',
                'Banco' => 'BANCOMER',
                'Cuenta' => '141480111',
                'Clabe' => '012910001414801118',
                'Tel_Contacto' => '9991224197',
                'Nombre_Contacto' => 'SERGIO PATRON',
                'Servicio' => 'MUBLES, ELECTRONICA AIRES ACONDICIONADOS'
            ],
            [
                'Nombre' => 'BOMSSA',
                'Nombre_Comercial' => 'BOMSSA',
                'RFC' => 'MEP141208NF3',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65504842212',
                'Clabe' => '014910655048422126',
                'Tel_Contacto' => '9992335144',
                'Nombre_Contacto' => 'EDGAR M',
                'Servicio' => 'MUBLES, ELECTRONICA AIRES ACONDICIONADOS'
            ],
            [
                'Nombre' => 'CAFIVER',
                'Nombre_Comercial' => 'CAFIVER',
                'RFC' => 'CAF820619U79',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65500440990',
                'Clabe' => '014882655004409907',
                'Tel_Contacto' => '9845931199',
                'Nombre_Contacto' => 'RUBEN G',
                'Servicio' => 'CAPSULAS DE CAFÉ PARA HOTEL'
            ],
            [
                'Nombre' => 'CARLOS ANTONIO BARRERA AGUILAR CERIMAT',
                'Nombre_Comercial' => 'CARLOS ANTONIO BARRERA AGUILAR CERIMat',
                'RFC' => 'BAAC7611198F3',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0148501246',
                'Clabe' => '012910001485012460',
                'Tel_Contacto' => '9996399777',
                'Nombre_Contacto' => 'MARY',
                'Servicio' => 'CONSTRURAMA, MATERIALES DE CONSTRUCCION'
            ],
            [
                'Nombre' => 'CARLOS FAUSTINO FARFAN CHAN (ELECTRONICA CANEK)',
                'Nombre_Comercial' => 'CARLOS FAUSTINO FARFAN CHAN (ELECTRONICA CANEK)',
                'RFC' => 'FACC801113S46',
                'Banco' => 'BANAMEX',
                'Cuenta' => '4045781879',
                'Clabe' => '021910040457818796',
                'Tel_Contacto' => '9991511339',
                'Nombre_Contacto' => 'CARLOS FARFAN',
                'Servicio' => 'REPARACION TVS Y ELECTRONICA'
            ],
            [
                'Nombre' => 'CARLOS RAFAEL CETINA',
                'Nombre_Comercial' => 'CARLOS RAFAEL CETINA',
                'RFC' => 'CEEC7811136C6',
                'Banco' => 'BANCO AZTECA',
                'Cuenta' => null,
                'Clabe' => '127910013570643782',
                'Tel_Contacto' => '99911162855',
                'Nombre_Contacto' => 'ROBERTO MEDINA ALBERTOS',
                'Servicio' => 'FUMIGACION DE PLAGAS / FUMIGUAY'
            ],
            [
                'Nombre' => 'CASA FERNANDEZ DEL SURESTE SA DE C',
                'Nombre_Comercial' => 'CASA FERNANDEZ DEL SURESTE SA DE C',
                'RFC' => 'CFS8606014IA',
                'Banco' => 'BANCOMER',
                'Cuenta' => '0150930776',
                'Clabe' => '012910001509307763',
                'Tel_Contacto' => '9993643177',
                'Nombre_Contacto' => 'CATALINA PACHO',
                'Servicio' => 'FERRETERIA'
            ],
            [
                'Nombre' => 'CASA SANTOS LUGO SA DE CV',
                'Nombre_Comercial' => 'CASA SANTOS LUGO SA DE CV',
                'RFC' => 'CSL000915HJ9',
                'Banco' => 'BANORTE',
                'Cuenta' => '0139238118',
                'Clabe' => '072910001392381185',
                'Tel_Contacto' => '9995105913',
                'Nombre_Contacto' => 'SAHIRA CASTRO',
                'Servicio' => 'ABARROTES'
            ],
            [
                'Nombre' => 'COMERCIALIZADORA DE CRISTALES DE ME',
                'Nombre_Comercial' => 'COMERCIALIZADORA DE CRISTALES DE ME',
                'RFC' => 'CCM130403B50',
                'Banco' => 'BANAMEX',
                'Cuenta' => '8095842',
                'Clabe' => '002910700480958429',
                'Tel_Contacto' => '9992180900',
                'Nombre_Contacto' => 'FERNANDO RIU',
                'Servicio' => 'TODO EN CUANTO A CRISTALES Y VIDRIOS'
            ],
            [
                'Nombre' => 'COMERCIALIZADORA E IMPORTADORA PALEMO',
                'Nombre_Comercial' => 'COMERCIALIZADORA E IMPORTADORA PALEMO',
                'RFC' => 'CIP160205QF4',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0110826936',
                'Clabe' => '012910001108269367',
                'Tel_Contacto' => '9992145536',
                'Nombre_Contacto' => 'RICARDO VALLE',
                'Servicio' => 'QUIMICOS LIMPIEZA Y JARCERIA'
            ],
            [
                'Nombre' => 'COMERCIALIZADORA SLIK',
                'Nombre_Comercial' => 'COMERCIALIZADORA SLIK',
                'RFC' => 'CAS230627JK5',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '996039592',
                'Clabe' => '012910001214624825',
                'Tel_Contacto' => '9996039592',
                'Nombre_Contacto' => 'ALFREDO CHI',
                'Servicio' => 'LAVADO COLCHONES'
            ],
            [
                'Nombre' => 'COMPAÑÍA DE AIRE ACONDICIONADO Y FRIGORIFICOS',
                'Nombre_Comercial' => 'COMPAÑÍA DE AIRE ACONDICIONADO Y FRIGORIFICOS',
                'RFC' => 'AAF2306305G0',
                'Banco' => 'BANCOMER',
                'Cuenta' => '0121354981',
                'Clabe' => '012910001213549817',
                'Tel_Contacto' => '9992690635',
                'Nombre_Contacto' => 'FELIX ALONSO ROMERO',
                'Servicio' => 'TECNICO EN CLIMAS, INSTALACION ETC.'
            ],
            [
                'Nombre' => 'COMPUFAX SA DE CV',
                'Nombre_Comercial' => 'COMPUFAX SA DE CV',
                'RFC' => 'COM910508749',
                'Banco' => 'INBURSA',
                'Cuenta' => '33001290016',
                'Clabe' => '036910330012900168',
                'Tel_Contacto' => '9999201416',
                'Nombre_Contacto' => 'WENDI TELLES-VICENTE REYES',
                'Servicio' => 'ELECTRONICA'
            ],
            [
                'Nombre' => 'CONSORCIO ZOUMA',
                'Nombre_Comercial' => 'CONSORCIO ZOUMA',
                'RFC' => 'CZ0230216891',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65509739877',
                'Clabe' => '014910655097398777',
                'Tel_Contacto' => '9992427020',
                'Nombre_Contacto' => 'EDUARDO AGUILAR',
                'Servicio' => 'MANTENIMIENTO DE PISOS DE CONCRETO'
            ],
            [
                'Nombre' => 'CONSTRUCTORES LOGAR SA DE CV',
                'Nombre_Comercial' => 'CONSTRUCTORES LOGAR SA DE CV',
                'RFC' => 'CLO100902N59',
                'Banco' => 'BANAMEX',
                'Cuenta' => '7066001',
                'Clabe' => '002910061670660017',
                'Tel_Contacto' => '9995472580',
                'Nombre_Contacto' => 'JAHAZIEL HERNANDEZ',
                'Servicio' => 'ALUMINIERO'
            ],
            [
                'Nombre' => 'CONTROL INTEGRAL DE COMBUSTiBLE',
                'Nombre_Comercial' => 'CONTROL INTEGRAL DE COMBUSTiBLE',
                'RFC' => 'CIC011107RR1',
                'Banco' => 'SANTANDER',
                'Cuenta' => null,
                'Clabe' => '014910655058602091',
                'Tel_Contacto' => '9993358600',
                'Nombre_Contacto' => 'LiC ERiCA RUIZ',
                'Servicio' => 'COMBUSTIBLES'
            ],
            [
                'Nombre' => 'COORPORATVO DE CAMONES PENiNSULA SA DE CV',
                'Nombre_Comercial' => 'COORPORATVO DE CAMONES PENiNSULA SA DE CV',
                'RFC' => 'APM860824IF1',
                'Banco' => 'BANCOMER',
                'Cuenta' => '168643288',
                'Clabe' => '012910001686432881',
                'Tel_Contacto' => '9999476164',
                'Nombre_Contacto' => 'ASESOR TALLER JESUS ESTRELLA',
                'Servicio' => 'MANTTO. CAMION HINO'
            ],
            [
                'Nombre' => 'CORPORATIVO INTERCERAMIC',
                'Nombre_Comercial' => 'CORPORATIVO INTERCERAMIC',
                'RFC' => 'AMS860820EX9',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0131914375',
                'Clabe' => '012910001319143751',
                'Tel_Contacto' => '9992022888',
                'Nombre_Contacto' => 'ROBERTO JASSO HERNANDEZ',
                'Servicio' => 'INTERCERAMIC'
            ],
            [
                'Nombre' => 'COSTCO DE MEXiCO SA CV',
                'Nombre_Comercial' => 'COSTCO DE MEXiCO SA CV',
                'RFC' => 'CME910715UB9',
                'Banco' => 'BBVA',
                'Cuenta' => null,
                'Clabe' => '012914002012801557',
                'Tel_Contacto' => '99998016200',
                'Nombre_Contacto' => 'LENNY GUZMAN/MARiO ZiTLE',
                'Servicio' => 'SUPER MERCADO'
            ],
            [
                'Nombre' => 'CREVAICA (QUIMICOS DE LIMPIEZA)',
                'Nombre_Comercial' => 'CREVAICA (QUIMICOS DE LIMPIEZA)',
                'RFC' => 'SCR190115D99',
                'Banco' => 'BANREGIO',
                'Cuenta' => '250007590012',
                'Clabe' => '058910000002734843',
                'Tel_Contacto' => '9991385813',
                'Nombre_Contacto' => 'RICARDO BAKTUN',
                'Servicio' => 'QUIMICOS LIMPIEZA Y JARCERIA'
            ],
            [
                'Nombre' => 'DANTE LUNA NORIEO',
                'Nombre_Comercial' => 'DANTE LUNA NORIEO',
                'RFC' => 'LUND670222475',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '108500053',
                'Clabe' => '012910001085000539',
                'Tel_Contacto' => '9999971779',
                'Nombre_Contacto' => 'LORENA CAAMAL',
                'Servicio' => 'LIMPIEZA DE COLCHONES, SOFAS, SILLA ETC.'
            ],
            [
                'Nombre' => 'DAVID ANTONIO HERRERA ESPADAS',
                'Nombre_Comercial' => 'DAVID ANTONIO HERRERA ESPADAS',
                'RFC' => 'HEED920613M96',
                'Banco' => 'HSBC',
                'Cuenta' => '0141480111',
                'Clabe' => '021910063916764782',
                'Tel_Contacto' => '9994528177',
                'Nombre_Contacto' => 'DAVID HERRERA',
                'Servicio' => 'SERV. LAVANDERIA'
            ],
            [
                'Nombre' => 'DISTRIBUIDORA FERRETERA OSITO S.A DE C.V',
                'Nombre_Comercial' => 'DISTRIBUIDORA FERRETERA OSITO S.A DE C.V',
                'RFC' => 'DFO2103021H3',
                'Banco' => 'BANORTE',
                'Cuenta' => '1171113838',
                'Clabe' => '072910011711138381',
                'Tel_Contacto' => '9993775467',
                'Nombre_Contacto' => 'MARIA',
                'Servicio' => 'FERRETERIA, HERRAMIENTAS, REFACCIONES'
            ],
            [
                'Nombre' => 'DISTRIBUIDORA DE ALUMINIO DEL MAYAB SA DE CV',
                'Nombre_Comercial' => 'DISTRIBUIDORA DE ALUMINIO DEL MAYAB SA DE CV',
                'RFC' => 'DAM970619481',
                'Banco' => 'HSBC',
                'Cuenta' => '4025085721',
                'Clabe' => '021910040250857213',
                'Tel_Contacto' => '9994429863',
                'Nombre_Contacto' => 'VENTAS MOSTRADOR',
                'Servicio' => 'FERRETERIA Y HERRAJES'
            ],
            [
                'Nombre' => 'DISTRIBUIDORA DE PRODUCTOS DE LIMPIEZA',
                'Nombre_Comercial' => 'DISTRIBUIDORA DE PRODUCTOS DE LIMPIEZA',
                'RFC' => 'DDP880318J34',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0448451699',
                'Clabe' => '012910004484516993',
                'Tel_Contacto' => '9999004142',
                'Nombre_Contacto' => 'KARINA AGUIÑAGA',
                'Servicio' => 'QUIMICOS LIMPIEZA Y JARCERIA'
            ],
            [
                'Nombre' => 'DiSTRiBUiDORA Gci SA CV',
                'Nombre_Comercial' => 'DiSTRiBUiDORA Gci SA CV',
                'RFC' => 'DGC191101720',
                'Banco' => 'BANAMEX',
                'Cuenta' => null,
                'Clabe' => '002910701577806441',
                'Tel_Contacto' => '9991274491',
                'Nombre_Contacto' => 'DANiEL RAMiREZ',
                'Servicio' => 'CARNES'
            ],
            [
                'Nombre' => 'ECOLAB S DE RL DE CV',
                'Nombre_Comercial' => 'ECOLAB S DE RL DE CV',
                'RFC' => 'ECO8703238B9',
                'Banco' => 'BANK OF AMERICA MEXICO SA',
                'Cuenta' => '14767025',
                'Clabe' => '106180000147670256',
                'Tel_Contacto' => '9992426618',
                'Nombre_Contacto' => 'EMIR SIERRA',
                'Servicio' => 'QUIMICOS DE LIMPIEZA'
            ],
            [
                'Nombre' => 'EL NIPLITO DEL SURESTE SA DE CV',
                'Nombre_Comercial' => 'EL NIPLITO DEL SURESTE SA DE CV',
                'RFC' => 'EBE7711037Y5',
                'Banco' => 'HSBC',
                'Cuenta' => '4001318815',
                'Clabe' => '021910040013188158',
                'Tel_Contacto' => '9992178558',
                'Nombre_Contacto' => 'ALEJANDRO UCAN',
                'Servicio' => 'MUEBLES,BAÑO, FERRETERIA ETC'
            ],
            [
                'Nombre' => 'ELIDE ELOISA SOSA CHUIL',
                'Nombre_Comercial' => 'ELIDE ELOISA SOSA CHUIL',
                'RFC' => 'SOCE851117D68',
                'Banco' => 'BANAMEX',
                'Cuenta' => '70178412572',
                'Clabe' => '002910701784125724',
                'Tel_Contacto' => '9993556135',
                'Nombre_Contacto' => 'ELIDE SOSA',
                'Servicio' => 'HERRERIA'
            ],
            [
                'Nombre' => 'EMBOTELLADORA BEPENSA SA CV',
                'Nombre_Comercial' => 'EMBOTELLADORA BEPENSA SA CV',
                'RFC' => 'EBE7711037Y5',
                'Banco' => 'SANTANDER',
                'Cuenta' => null,
                'Clabe' => '014180655058166901',
                'Tel_Contacto' => '9991023532',
                'Nombre_Contacto' => 'SUPERViSOR',
                'Servicio' => 'REFRESCOS COCA'
            ],
            [
                'Nombre' => 'GABRIEL BARANDA CASTILLA',
                'Nombre_Comercial' => 'GABRIEL BARANDA CASTILLA',
                'RFC' => 'BACG780117RU2',
                'Banco' => 'SANTANDER',
                'Cuenta' => '60532121329',
                'Clabe' => '014910605321213293',
                'Tel_Contacto' => '9993385481',
                'Nombre_Contacto' => 'LUIS MARTIN',
                'Servicio' => 'SUMINISTRA Y APLICA TRATAMIENTO PARA ELIMAR LA HUMEDAD'
            ],
            [
                'Nombre' => 'GAS Y DERiVADOS',
                'Nombre_Comercial' => 'GAS Y DERiVADOS',
                'RFC' => 'NSU9102113Y9',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65506094153',
                'Clabe' => '014910655060941533',
                'Tel_Contacto' => '9999429090',
                'Nombre_Contacto' => 'HARiS CHAVEZ',
                'Servicio' => 'GAS LP'
            ],
            [
                'Nombre' => 'GRUPO BOXITO',
                'Nombre_Comercial' => 'GRUPO BOXITO',
                'RFC' => 'GBO131031192',
                'Banco' => 'HSBC',
                'Cuenta' => '4056901200',
                'Clabe' => '021910040569012008',
                'Tel_Contacto' => '9993349213',
                'Nombre_Contacto' => 'ALVARO GAMBOA',
                'Servicio' => 'MUEBLES,BAÑO, FERRETERIA ETC'
            ],
            [
                'Nombre' => 'GRUPO ROMERUC SA CV',
                'Nombre_Comercial' => 'GRUPO ROMERUC SA CV',
                'RFC' => 'GRO130321A99',
                'Banco' => 'SCOTiABAN',
                'Cuenta' => '5600689370',
                'Clabe' => '044910256006893700',
                'Tel_Contacto' => '9993881830',
                'Nombre_Contacto' => 'JOSE ROMERO',
                'Servicio' => 'SUMINISTRO DE AGUA'
            ],
            [
                'Nombre' => 'HIFI PINTURAS DEL SURESTE',
                'Nombre_Comercial' => 'HIFI PINTURAS DEL SURESTE',
                'RFC' => 'HFP6109285M4',
                'Banco' => 'BANCOMER',
                'Cuenta' => '182476428',
                'Clabe' => '012910001824764285',
                'Tel_Contacto' => '9995504304',
                'Nombre_Contacto' => 'ADOLFO CANTO',
                'Servicio' => 'PINTURAS'
            ],
            [
                'Nombre' => 'HORECA',
                'Nombre_Comercial' => 'HORECA',
                'RFC' => 'RFI100210H12',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65507492436',
                'Clabe' => '014910655074924360',
                'Tel_Contacto' => null,
                'Nombre_Contacto' => 'MELISA MAY',
                'Servicio' => 'ACCESORIOS PARA COCINA'
            ],
            [
                'Nombre' => 'IMPREX',
                'Nombre_Comercial' => 'IMPREX',
                'RFC' => 'GOCA630211F66',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '00443834948',
                'Clabe' => '012910004438349488',
                'Tel_Contacto' => '9999285555',
                'Nombre_Contacto' => 'YENIFER MORENO',
                'Servicio' => 'IMPRESION DE DOCUMENTOS'
            ],
            [
                'Nombre' => 'JABONES Y PRODUCTOS ESPECIALIZADOS JYPESA',
                'Nombre_Comercial' => 'JABONES Y PRODUCTOS ESPECIALIZADOS JYPESA',
                'RFC' => 'JPE830408B35',
                'Banco' => 'BANORTE',
                'Cuenta' => '14752',
                'Clabe' => '072320006490147528',
                'Tel_Contacto' => '3335402939',
                'Nombre_Contacto' => 'BRENDA PIZARRO',
                'Servicio' => 'JABONES, SHAMPO P/HOTEL AMENIDADES'
            ],
            [
                'Nombre' => 'JOANA DE JESUS BENITEZ UC (HM PENINSULAR)',
                'Nombre_Comercial' => 'JOANA DE JESUS BENITEZ UC (HM PENINSULAR)',
                'RFC' => 'BEUJ8804306F2',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0479422935',
                'Clabe' => '012910004794229352',
                'Tel_Contacto' => '9995118042',
                'Nombre_Contacto' => 'JOANA BENITEZ UC',
                'Servicio' => 'PINTURA, FERRETERIA, MAT. CONSTRUCCION'
            ],
            [
                'Nombre' => 'JORGE YSIDRO EUAN CEN (REFRIELECTRO)',
                'Nombre_Comercial' => 'JORGE YSIDRO EUAN CEN (REFRIELECTRO)',
                'RFC' => 'EUCJ800515GZA',
                'Banco' => 'BANAMEX',
                'Cuenta' => '3530540',
                'Clabe' => '002910700435305409',
                'Tel_Contacto' => '9994164506',
                'Nombre_Contacto' => 'LUIS PECH',
                'Servicio' => 'MATERIALES Y REFACCIONES P/AIRES ACONDICIONADOS Y REFRIGERADORES'
            ],
            [
                'Nombre' => 'JOSE ROLANDO BEJAR HERRERA',
                'Nombre_Comercial' => 'JOSE ROLANDO BEJAR HERRERA',
                'RFC' => 'BEHR7002236I6',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65503955782',
                'Clabe' => '014910655039557822',
                'Tel_Contacto' => '9999826177',
                'Nombre_Contacto' => 'ROLANDO BEJAR',
                'Servicio' => 'RENTA EQ COPIADO Y TONER'
            ],
            [
                'Nombre' => 'KONE MEXICO SA DE CV',
                'Nombre_Comercial' => 'KONE MEXICO SA DE CV',
                'RFC' => 'KME880401DZ8',
                'Banco' => 'BANAMEX',
                'Cuenta' => '5896865',
                'Clabe' => '002180650558968658',
                'Tel_Contacto' => '9982416566',
                'Nombre_Contacto' => 'CELESTE CEN',
                'Servicio' => 'MANTENIMIENTO ELEVADOR'
            ],
            [
                'Nombre' => 'LB SISTEMAS',
                'Nombre_Comercial' => 'LB SISTEMAS',
                'RFC' => 'LSI090130BR5',
                'Banco' => 'BANAMEX',
                'Cuenta' => '8004347',
                'Clabe' => '002180057380043470',
                'Tel_Contacto' => '5586861901',
                'Nombre_Contacto' => 'JORDY JAKCSON',
                'Servicio' => 'EQUIPO DE COMPUTO'
            ],
            [
                'Nombre' => 'MANUEL ALEXANDRO PEREZ MALERVA/ AIRETIKA',
                'Nombre_Comercial' => 'MANUEL ALEXANDRO PEREZ MALERVA/ AIRETIKA',
                'RFC' => 'PEMM9602215E0',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '1522595419',
                'Clabe' => '012225015225954197',
                'Tel_Contacto' => '9997800447',
                'Nombre_Contacto' => 'MANUEL ALEXANDRO',
                'Servicio' => 'INSTALACION DE CLIMAS'
            ],
            [
                'Nombre' => 'MARTIN JOSE LOPEZ FLORES',
                'Nombre_Comercial' => 'MARTIN JOSE LOPEZ FLORES',
                'RFC' => 'LOFM890601DJ2',
                'Banco' => 'B AZTECA',
                'Cuenta' => '26281717',
                'Clabe' => '127910001250542908',
                'Tel_Contacto' => '9993951623',
                'Nombre_Contacto' => 'YANIRA JIMENEZ',
                'Servicio' => 'QUIMICOS DE LIMPIEZA'
            ],
            [
                'Nombre' => 'NERHY GILLESII MARTINEZ CACHON',
                'Nombre_Comercial' => 'NERHY GILLESII MARTINEZ CACHON',
                'RFC' => 'MACN841014NV1',
                'Banco' => 'BANORTE',
                'Cuenta' => '0851710112',
                'Clabe' => '072910008517101127',
                'Tel_Contacto' => '9992345436',
                'Nombre_Contacto' => 'RICARDO CACERES',
                'Servicio' => 'CONTROL DE PLAGAS'
            ],
            [
                'Nombre' => 'OFFICE DEPOT MEXICO SA DE CV',
                'Nombre_Comercial' => 'OFFICE DEPOT MEXICO SA DE CV',
                'RFC' => 'ODM950324V2A',
                'Banco' => 'BANAMEX',
                'Cuenta' => '7732356',
                'Clabe' => '002180010077323563',
                'Tel_Contacto' => '9997383686',
                'Nombre_Contacto' => 'MARIO VARGUEZ',
                'Servicio' => 'PAPELERIA, EQ. COMPUTO, MUEBLES P/OFICINA'
            ],
            [
                'Nombre' => 'OPERADORA DE TIENDAS VOLUNTARIAS SA DE CV',
                'Nombre_Comercial' => 'OPERADORA DE TIENDAS VOLUNTARIAS SA DE CV',
                'RFC' => 'OTV801119HU2',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65502390241',
                'Clabe' => '014910655023902412',
                'Tel_Contacto' => '9991188638',
                'Nombre_Contacto' => 'JOSEFINA CERVANTES',
                'Servicio' => 'PAPELERIA, RENTA COPIADORAS, MUEBLES P/OFICINA'
            ],
            [
                'Nombre' => 'PARTES Y EQUIPOS DE REFRIGERACION DEL SURESTE',
                'Nombre_Comercial' => 'PARTES Y EQUIPOS DE REFRIGERACION DEL SURESTE',
                'RFC' => 'PER920317PC8',
                'Banco' => 'SANTANDER',
                'Cuenta' => '50000044659',
                'Clabe' => '014910500000446596',
                'Tel_Contacto' => '9999473990',
                'Nombre_Contacto' => 'EDY CETINA',
                'Servicio' => 'REFACCIONES, PIEZAS, MATERIAL PARA AIRES'
            ],
            [
                'Nombre' => 'PISCINAS MASTER POOOL',
                'Nombre_Comercial' => 'PISCINAS MASTER POOOL',
                'RFC' => 'PMP121010C15',
                'Banco' => 'BANCOMER',
                'Cuenta' => '0192380595',
                'Clabe' => '012910001923805951',
                'Tel_Contacto' => '9999686627',
                'Nombre_Contacto' => 'EFRAIN SILVA',
                'Servicio' => 'QUIMICOS PARA PISCINA'
            ],
            [
                'Nombre' => 'POLIYUCAS',
                'Nombre_Comercial' => 'POLIYUCAS',
                'RFC' => 'POL140109US6',
                'Banco' => 'BANORTE',
                'Cuenta' => '0237693949',
                'Clabe' => '072910002376939497',
                'Tel_Contacto' => '9992634122',
                'Nombre_Contacto' => 'ITZAES',
                'Servicio' => 'DISTRIBUCION DE DESECHABLES,CONTENEDORES DE TODO TIPO, BOLSAS ETC.'
            ],
            [
                'Nombre' => 'PROMESSA PRODIN',
                'Nombre_Comercial' => 'PROMESSA PRODIN',
                'RFC' => 'PPR070511815',
                'Banco' => 'BANREGIO',
                'Cuenta' => '251008220037',
                'Clabe' => '058910000009862941',
                'Tel_Contacto' => '9999494663',
                'Nombre_Contacto' => 'PABLO RAMIREZ',
                'Servicio' => 'FERRETERIA, ELECTRICIDAD, PLOMERIA'
            ],
            [
                'Nombre' => 'RECOLECCIONES INDUSTRIALES SA DE CV',
                'Nombre_Comercial' => 'RECOLECCIONES INDUSTRIALES SA DE CV',
                'RFC' => 'RIN100518KK9',
                'Banco' => 'BANCO SANTANDER',
                'Cuenta' => '65506166439',
                'Clabe' => '014910655061664398',
                'Tel_Contacto' => '9992622980',
                'Nombre_Contacto' => 'MARIA',
                'Servicio' => 'LIMPIEZA DE BIODIGESTORES, DRENAJES,FOSA..'
            ],
            [
                'Nombre' => 'REFRIMART AGUILAR',
                'Nombre_Comercial' => 'REFRIMART AGUILAR',
                'RFC' => 'MRE070622J84',
                'Banco' => 'BBVA Bancomer',
                'Cuenta' => '169889094',
                'Clabe' => '012910001698890945',
                'Tel_Contacto' => '9994424144',
                'Nombre_Contacto' => 'LEVI QUINTAL',
                'Servicio' => 'MATERIALES Y REFACCIONES P/AIRES ACONDICIONADOS Y REFRIGERADORES'
            ],
            [
                'Nombre' => 'REMODELACION Y REPARACION ONLI MANTENIMIENTO',
                'Nombre_Comercial' => 'REMODELACION Y REPARACION ONLI MANTENIMIENTO',
                'RFC' => 'RRO230809MA8',
                'Banco' => 'SCOTIABANK',
                'Cuenta' => '25605060577',
                'Clabe' => '044910256050605779',
                'Tel_Contacto' => '9991417368',
                'Nombre_Contacto' => 'ERIK SANCHEZ QUINTAL',
                'Servicio' => 'REPARACION, INSTALACION, Y MAS DE CLIMAS'
            ],
            [
                'Nombre' => 'RITCO INDUSTRIAL',
                'Nombre_Comercial' => 'RITCO INDUSTRIAL',
                'RFC' => 'RIN960826P22',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0149049045',
                'Clabe' => '012691001490490451',
                'Tel_Contacto' => '9992174546',
                'Nombre_Contacto' => 'CARLOS FRANCO',
                'Servicio' => 'QUIMICOS DE LIMPIEZA'
            ],
            [
                'Nombre' => 'ROSA CANDELARIA VERA MAGAÑA',
                'Nombre_Comercial' => 'ROSA CANDELARIA VERA MAGAÑA',
                'RFC' => 'VEMR660906BY8',
                'Banco' => 'BANAMEX',
                'Cuenta' => '7833190',
                'Clabe' => '002914700978331907',
                'Tel_Contacto' => null,
                'Nombre_Contacto' => 'ROSA VERA',
                'Servicio' => 'FRUTAS Y VERDURAS'
            ],
            [
                'Nombre' => 'ROTULOS COMPUTARIZADOS E IMPRESOS DEL SURESTE',
                'Nombre_Comercial' => 'ROTULOS COMPUTARIZADOS E IMPRESOS DEL SURESTE',
                'RFC' => 'RCI120828K64',
                'Banco' => 'SANTANDER',
                'Cuenta' => '65503644150',
                'Clabe' => '014910655036441504',
                'Tel_Contacto' => '9994867012',
                'Nombre_Contacto' => 'ARACELY',
                'Servicio' => 'IMPRENTA'
            ],
            [
                'Nombre' => 'SANEAMIENTO SANA',
                'Nombre_Comercial' => 'SANEAMIENTO SANA',
                'RFC' => 'SSA030409P68',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '1726617',
                'Clabe' => '012914002017266171',
                'Tel_Contacto' => '9999442472',
                'Nombre_Contacto' => 'ATENCION CLIENTES',
                'Servicio' => 'RECOLECTA DE BASUR A'
            ],
            [
                'Nombre' => 'SERVICIOS PENINSULARES NOGAL',
                'Nombre_Comercial' => 'SERVICIOS PENINSULARES NOGAL',
                'RFC' => 'SPN190218948',
                'Banco' => 'BANCOMER',
                'Cuenta' => '0112996898',
                'Clabe' => '012910001129968980',
                'Tel_Contacto' => '9991719671',
                'Nombre_Contacto' => 'CARLOS',
                'Servicio' => 'SERVICIOS DE SEGURIDAD PRIVADA'
            ],
            [
                'Nombre' => 'SISTEMAS EN RECUBRIMIENTOS INDUSTRIALES SA DE CV',
                'Nombre_Comercial' => 'SISTEMAS EN RECUBRIMIENTOS INDUSTRIALES SA DE CV',
                'RFC' => 'SRI120529RP8',
                'Banco' => 'HSBC',
                'Cuenta' => '4054561485',
                'Clabe' => '021910040545614800',
                'Tel_Contacto' => '9999493275',
                'Nombre_Contacto' => 'MANUEL CHUC',
                'Servicio' => 'MATERIALES PARA TRATAMIENTOS HIDROFUGANTES'
            ],
            [
                'Nombre' => 'THE HOME DEPOT MEXICO S DE RL DE CV',
                'Nombre_Comercial' => 'THE HOME DEPOT MEXICO S DE RL DE CV',
                'RFC' => 'HDM001017AS1',
                'Banco' => 'BANAMEX',
                'Cuenta' => '7730557',
                'Clabe' => '002580008777305574',
                'Tel_Contacto' => '9992308389',
                'Nombre_Contacto' => 'GLORIA GONZALEZ',
                'Servicio' => 'FERRETERIA, ELECTRONICA ETC'
            ],
            [
                'Nombre' => 'ULINE SHIPPING SUPPLIES S DE RL DE CV',
                'Nombre_Comercial' => 'ULINE SHIPPING SUPPLIES S DE RL DE CV',
                'RFC' => 'USS000718PA0',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0453178900',
                'Clabe' => '012028004531789002',
                'Tel_Contacto' => '8181567400',
                'Nombre_Contacto' => 'LUIS SANTANA',
                'Servicio' => 'TIENDA EN LINEA'
            ],
            [
                'Nombre' => 'UNIFORMES TAMPICO',
                'Nombre_Comercial' => 'UNIFORMES TAMPICO',
                'RFC' => 'UTA820628TV3',
                'Banco' => 'BBVA BANCOMER',
                'Cuenta' => '0117942249',
                'Clabe' => '012813001179422492',
                'Tel_Contacto' => '9992678774',
                'Nombre_Contacto' => 'LISSET',
                'Servicio' => 'UNIFORMES'
            ]
        ];

        // Insertar con validación individual
        $insertados = 0;
        foreach ($proveedores as $proveedor) {
            // Limpiar y formatear datos numéricos
            if (!empty($proveedor['Cuenta'])) {
                $proveedor['Cuenta'] = preg_replace('/[^0-9]/', '', $proveedor['Cuenta']);
            }
            if (!empty($proveedor['Clabe'])) {
                $proveedor['Clabe'] = preg_replace('/[^0-9]/', '', $proveedor['Clabe']);
            }
            if (!empty($proveedor['Tel_Contacto'])) {
                $proveedor['Tel_Contacto'] = preg_replace('/[^0-9]/', '', $proveedor['Tel_Contacto']);
            }

            $exists = $this->db->table('Proveedor')
                ->where('RFC', $proveedor['RFC'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('Proveedor')->insert($proveedor);
                $insertados++;
                log_message('info', '[Migración] Insertado proveedor: ' . $proveedor['Nombre']);
            }
        }

        log_message('info', "[Migración] Total de proveedores insertados: {$insertados}");
    }

    public function down()
    {
        //
    }
}
