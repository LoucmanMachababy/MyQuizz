use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    #[Route('/whoami')]
    public function whoami(): Response
    {
        $user = $this->getUser();
        return new Response(
            $user ? 'Logged in as: ' . $user->getUserIdentifier() . ' | Roles: ' . implode(', ', $user->getRoles()) : 'Not logged in'
        );
    }
}
