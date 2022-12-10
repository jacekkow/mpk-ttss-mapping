<?php
class BusTypes extends VehicleTypes {
	public function __construct() {
		$data = <<<'END'
1	4	DN	Solaris Urbino 18 IV Electric
5	6	DN	Solaris Urbino 18 III Electric
7	56	DN	Solaris Urbino 18 IV Electric
71	83	BH	Solaris Urbino 18 III Hybrid
84	95	BH	Volvo 7900A Hybrid
100	101	PA	Karsan Jest
102	102	PA	Mercedes-Benz Sprinter City 75
103	105	PA	Mercedes-Benz 516
106	112	DA	Autosan M09LE
113	121	BA	Autosan M09LE
122	132	DA	Autosan M09LE
133	141	BA	Autosan M09LE
200	200	DO	Mercedes Conecto
201	201	DO	Mercedes Conecto II
206	210	PO	Mercedes O530 C2 Hybrid
211	218	DO	Mercedes O530
219	243	PO	Mercedes O530 C2 Hybrid
244	269	DO	Mercedes O530 C2
270	299	BO	Mercedes O530 C2
301	335	DU	Solaris Urbino 12 IV
336	336	BU	Solaris Urbino 12 IV
337	338	PU	Solaris Urbino 12 IV
339	340	BU	Solaris Urbino 12 IV
341	349	KU	Solaris Urbino 12 IV
400	403	BH	Solaris Urbino 12,9 III Hybrid
404	408	DH	Solaris Urbino 12,9 III Hybrid
409	409	BH	Volvo 7900 Hybrid
490	499	KM	MAN Lion's Intercity 13
501	510	BR	Solaris Urbino 18 IV
511	560	DR	Solaris Urbino 18 IV
561	579	BR	Solaris Urbino 18 IV
580	595	DR	Solaris Urbino 18 IV
596	598	KR	Solaris Urbino 18 IV
601	601	DE	Solaris Urbino 12 III Electric
602	605	DE	Solaris Urbino 8,9LE Electric
606	606	DE	Solaris Urbino 12 III Electric
607	623	DE	Solaris Urbino 12 IV Electric
700	700	DC	Mercedes Conecto G
701	731	DC	Mercedes O530G
732	734	DC	Mercedes Conecto G
737	741	BR	Solaris Urbino 18 III
742	745	DR	Solaris Urbino 18 III
746	768	PR	Solaris Urbino 18 III
769	776	PR	Solaris Urbino 18 MetroStyle
777	777	PR	Solaris Urbino 18 III
778	797	PR	Solaris Urbino 18 IV
851	926	BU	Solaris Urbino 12 III
927	976	PU	Solaris Urbino 12 III
977	977	BU	Solaris Urbino 12 III
978	991	PU	Solaris Urbino 12 IV
992	997	BU	Solaris Urbino 12 IV
END;
		parent::__construct($data, 2);
	}
}
