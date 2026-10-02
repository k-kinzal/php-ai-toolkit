<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function count;
use function sprintf;

use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * Renders the private members of a class-like symbol.
 *
 * The section stands on its own rather than trailing the last public
 * member, so the signatures are never read as part of it.
 */
final class PrivateSurfaceHtml
{
    /** @readonly */
    private SignatureHtml $signature;

    /**
     * Creates a private surface renderer from its signature renderer.
     */
    public function __construct(?SignatureHtml $signature = null)
    {
        $this->signature = $signature ?? new SignatureHtml();
    }

    /**
     * Collects the private members of a class-like symbol.
     *
     * @return list<\Toolkit\DocGen\Analysis\Model\ConstantDoc|\Toolkit\DocGen\Analysis\Model\PropertyDoc|\Toolkit\DocGen\Analysis\Model\MethodDoc>
     */
    public function members(ClassLikeDoc $classLike): array
    {
        $private = [];
        foreach ([$classLike->constants, $classLike->properties, $classLike->methods] as $members) {
            foreach ($members as $member) {
                if ($member->visibility === 'private') {
                    $private[] = $member;
                }
            }
        }

        return $private;
    }

    /**
     * Lists the state of every private member, in the rendered order.
     *
     * @return list<string>
     */
    public function statuses(RenderKit $services, ClassLikeDoc $classLike): array
    {
        $diff = $services->diff;
        $statuses = [];
        foreach ($classLike->constants as $constant) {
            if ($constant->visibility === 'private') {
                $statuses[] = $diff->memberStatus($classLike->fqcn, DiffKey::CONSTANT, $constant->name);
            }
        }

        foreach ($classLike->properties as $property) {
            if ($property->visibility === 'private') {
                $statuses[] = $diff->memberStatus($classLike->fqcn, DiffKey::PROPERTY, $property->name);
            }
        }

        foreach ($classLike->methods as $method) {
            if ($method->visibility === 'private') {
                $statuses[] = $diff->memberStatus($classLike->fqcn, DiffKey::METHOD, $method->name);
            }
        }

        return $statuses;
    }

    /**
     * Renders the private surface section, or nothing when there is none.
     */
    public function section(RenderKit $services, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $rows = $this->rows($services, $classLike, $context);
        if ($rows === []) {
            return '';
        }

        $html = '<section class="private-surface"' . $services->diff->combined($this->statuses($services, $classLike)) . '><h2 id="private-surface">Private surface'
            . sprintf(' <span class="count">%d</span>', count($rows))
            . '<a class="anchor" href="#private-surface">§</a></h2>'
            . '<p class="section-note">Implementation details, listed for orientation only.</p>';
        foreach ($rows as $row) {
            $html .= '<pre class="member-sig private-sig"' . $services->diff->mark($row['status']) . '><code>' . $row['html'] . '</code></pre>';
        }

        return $html . '</section>' . "\n";
    }

    /**
     * Renders the signature of every private member with its diff state.
     *
     * @return list<array{html: string, status: string}>
     */
    public function rows(RenderKit $services, ClassLikeDoc $classLike, TypeRenderContext $context): array
    {
        $diff = $services->diff;
        $rows = [];
        foreach ($classLike->constants as $constant) {
            if ($constant->visibility === 'private') {
                $rows[] = [
                    'html' => $this->signature->constantSignature($services, $constant, $context),
                    'status' => $diff->memberStatus($classLike->fqcn, DiffKey::CONSTANT, $constant->name),
                ];
            }
        }

        foreach ($classLike->properties as $property) {
            if ($property->visibility === 'private') {
                $rows[] = [
                    'html' => $this->signature->propertySignature($services, $property, $context),
                    'status' => $diff->memberStatus($classLike->fqcn, DiffKey::PROPERTY, $property->name),
                ];
            }
        }

        foreach ($classLike->methods as $method) {
            if ($method->visibility === 'private') {
                $key = $diff->methodKey($classLike->fqcn, $method->name);
                $rows[] = [
                    'html' => $this->signature->methodSignature($services, $method, $context, $key),
                    'status' => $diff->statusOf($key),
                ];
            }
        }

        return $rows;
    }
}
