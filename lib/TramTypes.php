<?php
class TramTypes extends VehicleTypes {
	public function __construct() {
		$data = <<<'END'
101	107	HW	E1	0
108	113	RW	E1	0
114	126	HW	E1	0
127	127	RW	E1	0
128	130	HW	E1	0
131	132	RW	E1	0
133	133	HW	E1	0
134	134	RW	E1	0
135	136	HW	E1	0
137	139	RW	E1	0
140	147	HW	E1	0
148	150	RW	E1	0
151	152	HW	E1	0
153	153	RW	E1	0
154	154	HW	E1	0
155	155	RW	E1	0
156	158	HW	E1	0
159	159	RW	E1	0
160	174	HW	E1	0
201	206	RZ	105N	0
207	208	HZ	105N	0
209	245	RZ	105N	0
246	299	HZ	105N	0
301	302	RF	GT8N	1
304	309	RF	GT8N	1
310	313	RF	GT8C	1
314	329	RF	GT8N	1
401	440	HL	EU8N	1
451	456	HK	N8C-NF	1
457	461	HK	N8S-NF	1
462	462	HK	N8C-NF	1
601	614	RP	NGT6 (1)	2
615	626	RP	NGT6 (2)	2
627	650	RP	NGT6 (3)	2
701	736	HY	Stadler Tango II	2
801	824	RY	NGT8	2
825	839	RY	Stadler Tango	2
840	874	HY	Stadler Tango	2
875	898	RY	Stadler Tango II	2
899	899	RY	126N	2
901	914	RG	2014N	2
915	936	HG	2014N	2
999	999	HG	405N	1
END;
		parent::__construct($data);
	}
	
	public function getByNumber($id) {
		return parent::getByNumber($id);
	}
}
