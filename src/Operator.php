<?php

namespace Tggl\Client;

class Operator
{
    const Empty = 'EMPTY';
    const True = 'TRUE';
    const StrEqual = 'STR_EQUAL';
    const StrEqualSoft = 'STR_EQUAL_SOFT';
    const StrStartsWith = 'STR_STARTS_WITH';
    const StrEndsWith = 'STR_ENDS_WITH';
    const StrContains = 'STR_CONTAINS';
    const Percentage = 'PERCENTAGE';
    const ArrOverlap = 'ARR_OVERLAP';
    const RegExp = 'REGEXP';
    const StrBefore = 'STR_BEFORE';
    const StrAfter = 'STR_AFTER';
    const Eq = 'EQ';
    const Lt = 'LT';
    const Gt = 'GT';
    const DateAfter = 'DATE_AFTER';
    const DateBefore = 'DATE_BEFORE';
    const SemverEq = 'SEMVER_EQ';
    const SemverGte = 'SEMVER_GTE';
    const SemverLte = 'SEMVER_LTE';
    const UaBrowser = 'UA_BROWSER';
    const UaOs = 'UA_OS';
    const LangCountry = 'LANG_COUNTRY';
    const LangLang = 'LANG_LANG';
}