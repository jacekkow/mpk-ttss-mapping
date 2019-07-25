<?php
class BusTypes extends VehicleTypes {
	public function __construct() {
		$data = <<<'END'
2	4	DN	Solaris Urbino 18 IV Electric
71	83	BH	Solaris Urbino 18 III Hybrid
84	96	BH	Volvo 7900A Hybrid
103	105	PA	Mercedes-Benz 516
106	112	DA	Autosan M09LE
113	121	BA	Autosan M09LE
122	132	DA	Autosan M09LE
133	138	BA	Autosan M09LE
139	139	DA	Autosan M12LF
200	200	DO	Mercedes Conecto
206	210	PO	Mercedes O530 C2 Hybrid
211	218	DO	Mercedes O530
219	243	PO	Mercedes O530 C2 Hybrid
244	269	DO	Mercedes O530 C2
270	299	BO	Mercedes O530 C2
301	335	DU	Solaris Urbino 12 IV
336	336	BU	Solaris Urbino 12 IV
337	338	PU	Solaris Urbino 12 IV
339	340	BU	Solaris Urbino 12 IV
341	345	PU	Solaris Urbino 12 III
400	403	BH	Solaris Urbino 12,9 III Hybrid
404	408	DH	Solaris Urbino 12,9 III Hybrid
410	410	DH	Solaris Urbino 12 III Hybrid
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
732	733	DC	Mercedes Conecto G
737	741	BR	Solaris Urbino 18 III
742	745	DR	Solaris Urbino 18 III
746	764	PR	Solaris Urbino 18 III
765	768	DR	Solaris Urbino 18 III
769	776	PR	Solaris Urbino 18 MetroStyle
777	777	DR	Solaris Urbino 18 III
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
