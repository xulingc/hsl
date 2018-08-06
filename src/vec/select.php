<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\Vec;

use namespace HH\Lib\{Dict, Keyset};

/**
 * Returns a new vec containing only the elements of the first Traversable that
 * do not appear in any of the other ones.
 *
 * For vecs that contain non-arraykey elements, see `Vec\diff_by()`.
 */
function diff<Tv1 as arraykey, Tv2 as arraykey>(
  Traversable<Tv1> $first,
  Traversable<Tv2> $second,
  Traversable<Tv2> ...$rest
): vec<Tv1> {
  if (!$first) {
    return vec[];
  }
  if (!$second && !$rest) {
    return vec($first);
  }
  $union = !$rest
    ? keyset($second)
    : Keyset\union($second, ...$rest);
  return filter(
    $first,
    <<__Rx>> ($value) ==> !\array_key_exists($value, $union),
  );
}

/**
 * Returns a new vec containing only the elements of the first Traversable
 * that do not appear in the second one, where an element's identity is
 * determined by the scalar function.
 *
 * For vecs that contain arraykey elements, see `Vec\diff()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function diff_by<Tv, Ts as arraykey>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $first,
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $second,
  <<__OnlyRxIfRxFunc>>
  (function(Tv): Ts) $scalar_func,
): vec<Tv> {
  if (!$first) {
    return vec[];
  }
  if (!$second) {
    return vec($first);
  }
  $set = Keyset\map($second, $scalar_func);
  return filter(
    $first,
    <<__RxOfScope>> ($value) ==> !\array_key_exists($scalar_func($value), $set),
  );
}

/**
 * Returns a new vec containing all except the first `$n` elements of the
 * given Traversable.
 *
 * To take only the first `$n` elements, see `Vec\take()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function drop<Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $traversable,
  int $n,
): vec<Tv> {
  invariant($n >= 0, 'Expected non-negative N, got %d.', $n);
  $result = vec[];
  $ii = -1;
  foreach ($traversable as $value) {
    $ii++;
    if ($ii < $n) {
      continue;
    }
    $result[] = $value;
  }
  return $result;
}

/**
 * Returns a new vec containing only the values for which the given predicate
 * returns `true`. The default predicate is casting the value to boolean.
 *
 * - To remove null values in a typechecker-visible way, see
 *   `Vec\filter_nulls()`.
 * - To use an async predicate, see `Vec\filter_async()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function filter<Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $traversable,
  <<__OnlyRxIfRxFunc>>
  ?(function(Tv): bool) $value_predicate = null,
): vec<Tv> {
  $value_predicate = $value_predicate ?? fun('\\HH\\Lib\\_Private\\boolval');
  $result = vec[];
  foreach ($traversable as $value) {
    if ($value_predicate($value)) {
      $result[] = $value;
    }
  }
  return $result;
}

/**
 * Returns a new vec containing only non-null values of the given
 * Traversable.
 */
<<__Rx, __OnlyRxIfArgs>>
function filter_nulls<Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<?Tv> $traversable,
): vec<Tv> {
  $result = vec[];
  foreach ($traversable as $value) {
    if ($value !== null) {
      $result[] = $value;
    }
  }
  return $result;
}

/**
 * Returns a new vec containing only the values for which the given predicate
 * returns `true`.
 *
 * If you don't need access to the key, see `Vec\filter()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function filter_with_key<Tk, Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\KeyedTraversable::class)>>
  KeyedTraversable<Tk, Tv> $traversable,
  <<__OnlyRxIfRxFunc>>
  (function(Tk, Tv): bool) $predicate,
): vec<Tv> {
  $result = vec[];
  foreach ($traversable as $key => $value) {
    if ($predicate($key, $value)) {
      $result[] = $value;
    }
  }
  return $result;
}

/**
 * Returns a new vec containing only the elements of the first Traversable that
 * appear in all the other ones. Duplicate values are preserved.
 */
function intersect<Tv as arraykey>(
  Traversable<Tv> $first,
  Traversable<Tv> $second,
  Traversable<Tv> ...$rest
): vec<Tv> {
  $intersection = Keyset\intersect($first, $second, ...$rest);
  if (!$intersection) {
    return vec[];
  }
  return filter(
    $first,
    <<__Rx>> ($value) ==> \array_key_exists($value, $intersection),
  );
}

/**
  * Returns a new vec containing the keys of the given KeyedTraversable.
  */
<<__Rx, __OnlyRxIfArgs>>
function keys<Tk, Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\KeyedTraversable::class)>>
  KeyedTraversable<Tk, Tv> $traversable,
): vec<Tk> {
  $result = vec[];
  foreach ($traversable as $key => $_) {
    $result[] = $key;
  }
  return $result;
}

/**
 * Returns a new vec containing an unbiased random sample of up to
 * `$sample_size` elements (fewer iff `$sample_size` is larger than the size of
 * `$traversable`).
 */
function sample<Tv>(
  Traversable<Tv> $traversable,
  int $sample_size,
): vec<Tv> {
  invariant(
    $sample_size >= 0,
    'Expected non-negative sample size, got %d.',
    $sample_size,
  );
  return $traversable
    |> shuffle($$)
    |> take($$, $sample_size);
}

/**
 * Returns a new vec containing the subsequence of the given Traversable
 * determined by the offset and length.
 *
 * If no length is given or it exceeds the upper bound of the Traversable,
 * the vec will contain every element after the offset.
 *
 * - To take only the first `$n` elements, see `Vec\take()`.
 * - To drop the first `$n` elements, see `Vec\drop()`.
 */
<<__Rx>>
function slice<Tv>(
  Container<Tv> $container,
  int $offset,
  ?int $length = null,
): vec<Tv> {
  invariant($offset >= 0, 'Expected non-negative offset.');
  invariant($length === null || $length >= 0, 'Expected non-negative length.');
  return vec(\array_slice($container, $offset, $length));
}

/**
 * Returns a new vec containing the first `$n` elements of the given
 * Traversable.
 *
 * To drop the first `$n` elements, see `Vec\drop()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function take<Tv>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $traversable,
  int $n,
): vec<Tv> {
  if ($n === 0) {
    return vec[];
  }
  invariant($n > 0, 'Expected non-negative N, got %d.', $n);
  $result = vec[];
  $ii = 0;
  foreach ($traversable as $value) {
    $result[] = $value;
    $ii++;
    if ($ii === $n) {
      break;
    }
  }
  return $result;
}

/**
 * Returns a new vec containing each element of the given Traversable exactly
 * once. The Traversable must contain arraykey values, and strict equality will
 * be used.
 *
 * For non-arraykey elements, see `Vec\unique_by()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function unique<Tv as arraykey>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $traversable,
): vec<Tv> {
  return vec(keyset($traversable));
}

/**
 * Returns a new vec containing each element of the given Traversable exactly
 * once, where uniqueness is determined by calling the given scalar function on
 * the values. In case of duplicate scalar keys, later values will overwrite
 * previous ones.
 *
 * For arraykey elements, see `Vec\unique()`.
 */
<<__Rx, __OnlyRxIfArgs>>
function unique_by<Tv, Ts as arraykey>(
  <<__MaybeMutable, __OnlyRxIfImpl(\HH\Rx\Traversable::class)>>
  Traversable<Tv> $traversable,
  <<__OnlyRxIfRxFunc>>
  (function(Tv): Ts) $scalar_func,
): vec<Tv> {
  return vec(Dict\from_values($traversable, $scalar_func));
}
