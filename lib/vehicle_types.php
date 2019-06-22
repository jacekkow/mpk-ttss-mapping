<?php
function numToType($id, $data, $defaultLow=NULL) {
	$data = explode("\n", trim($data));
	foreach($data as $line) {
		$line = explode("\t", trim($line));
		if((int)$line[0] <= (int)$id && (int)$id <= (int)$line[1]) {
			return [
				'num' => $line[2] . str_pad($id, 3, '0', STR_PAD_LEFT),
				'type' => $line[3],
				'low' => (int)(isset($line[4]) ? $line[4] : $defaultLow),
			];
		}
	}
	return [
		'num' => '??'.$id,
		'type' => '?',
		'low' => NULL,
	];
}
function numToTypeT($id) {
	if((int)$id == 250) {
		$id = 410;
	}
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
201	245	RZ	105N	0
246	299	HZ	105N	0
301	312	RF	GT8S	0
313	313	RF	GT8C	1
314	322	RF	GT8S	0
323	323	RF	GT8N	1
324	324	RF	GT8S	0
325	329	RF	GT8N	1
401	440	HL	EU8N	1
451	456	HK	N8C-NF	0
457	461	HK	N8S-NF	1
462	462	HK	N8C-NF	0
601	614	RP	NGT6 (1)	2
615	626	RP	NGT6 (2)	2
627	650	RP	NGT6 (3)	2
801	824	RY	NGT8	2
899	899	RY	126N	2
901	914	RG	2014N	2
915	936	HG	2014N	2
999	999	HG	405N	1
END;
	return numToType($id, $data);
}
function numToTypeB($id) {
$data = <<<'END'
2	4	DN	Solaris Urbino 18 IV Electric
71	83	BH	Solaris Urbino 18 III Hybrid
84	96	BH	Volvo 7900A Hybrid
103	105	PA	Mercedes-Benz 516
106	112	DA	Autosan M09LE
113	121	BA	Autosan M09LE
122	128	DA	Autosan M09LE
129	139	BA	Autosan M09LE
141	146	PM	MAN NL283 Lion's City
200	200	DO	Mercedes Conecto
206	210	PO	Mercedes O530 C2 Hybrid
211	218	DO	Mercedes O530
219	243	PO	Mercedes O530 C2 Hybrid
244	269	DO	Mercedes O530 C2
270	299	BO	Mercedes O530 C2
301	338	DU	Solaris Urbino 12 IV
339	340	BU	Solaris Urbino 12 IV
341	345	DU	Solaris Urbino 12 III
400	403	BH	Solaris Urbino 12,9 III Hybrid
404	408	DH	Solaris Urbino 12,9 III Hybrid
501	510	BR	Solaris Urbino 18 IV
511	568	DR	Solaris Urbino 18 IV
569	579	BR	Solaris Urbino 18 IV
580	595	DR	Solaris Urbino 18 IV
601	601	DE	Solaris Urbino 12 III Electric
602	605	DE	Solaris Urbino 8,9LE Electric
606	606	DE	Solaris Urbino 12 III Electric
607	623	DE	Solaris Urbino 12 IV Electric
700	700	DC	Mercedes Conecto G
701	731	DC	Mercedes O530G
732	732	DC	Mercedes Conecto G
737	741	BR	Solaris Urbino 18 III
742	745	DR	Solaris Urbino 18 III
746	764	PR	Solaris Urbino 18 III
765	768	DR	Solaris Urbino 18 III
769	776	PR	Solaris Urbino 18 MetroStyle
777	777	DR	Solaris Urbino 18 III
778	797	PR	Solaris Urbino 18 IV
851	903	BU	Solaris Urbino 12 III
904	905	DU	Solaris Urbino 12 III
906	926	BU	Solaris Urbino 12 III
927	976	PU	Solaris Urbino 12 III
977	977	DU	Solaris Urbino 12 III
978	991	PU	Solaris Urbino 12 IV
992	997	BU	Solaris Urbino 12 IV
END;
	return numToType($id, $data, 2);
}
